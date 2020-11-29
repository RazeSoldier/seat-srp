<?PHP

namespace Denngarr\Seat\SeatSrp\Http\Controllers;

use Denngarr\Seat\SeatSrp\Http\Resources\ActionLog;
use Denngarr\Seat\SeatSrp\Models\{
    Sde\InvFlag,
    Sde\InvType,
    ShipCost,
    SrpActionLog
};
use Denngarr\Seat\SeatSrp\Models\KillMail;
use Denngarr\Seat\SeatSrp\Validation\AddKillMail;
use Denngarr\Seat\SeatSrp\Util;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\{
    Spreadsheet,
    Writer\Xls
};
use Seat\Eseye\Eseye;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Seat\Services\Settings\Profile;
use Seat\Web\Http\Controllers\Controller;
use Seat\Eveapi\Models\Character\CharacterInfo;
use stdClass;

class SrpController extends Controller {

    public function srpGetRequests()
    {
        $kills = KillMail::where('user_id', auth()->user()->id)
                         ->orderby('created_at', 'desc')
                         ->take(20)
                         ->get();
        $lang = Profile::get('language');
        if ($lang === 'cn') {
            $lang = 'zh';
        } else {
            $lang = 'en';
        }
        foreach ($kills as $kill) {
            $kill->ship_type = Util::translateShipType($kill->ship_type, $lang);
        }
        return view('srp::request', compact('kills'));
    }

    public function srpGetKillMail(Request $request)
    {
        $totalKill = [];

        $response = (new Client())->request('GET', $request->km);

        $killMail = json_decode($response->getBody());
        $totalKill = array_merge($totalKill, $this->srpPopulateSlots($killMail));
        preg_match('/([a-z0-9]{35,42})/', $request->km, $tokens);
        $totalKill['killToken'] = $tokens[0];
        $totalKill['killTime'] = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $killMail->killmail_time)
            ->format('Y-m-d H:i:s');

        return response()->json($totalKill);
    }

    public function srpSaveKillMail(AddKillMail $request)
    {
        if (auth()->user()->name !== $request->input('srpCharacterName')) {
            return redirect()->back()
                ->with('error', trans('srp::srp.name_mismatch'));
        }
        $corpId = CharacterInfo::whereName($request->input('srpCharacterName'))->first()->affiliation->corporation_id;
        KillMail::create([
            'user_id'        => auth()->user()->id,
            'user_name'      => auth()->user()->name,
            'character_name' => $request->input('srpCharacterName'),
            'kill_id'        => $request->input('srpKillId'),
            'kill_token'     => $request->input('srpKillToken'),
            'approved'       => 0,
            'cost'           => $request->input('srpCost'),
            'type_id'        => $request->input('srpTypeId'),
            'ship_type'      => $request->input('srpShipType'),
            'corp_id'        => $corpId,
            'corp_name'        => CorporationInfo::find($corpId)->name,
            'kill_time'        => $request->input('srpKillTime'),
        ]);

        if (!is_null($request->input('srpPingContent')) && $request->input('srpPingContent') != '')
        	KillMail::addNote($request->input('srpKillId'), 'ping', $request->input('srpPingContent'));

        return redirect()->back()
                         ->with('success', trans('srp::srp.submitted'));
    }

	public function getInsurances($kill_id)
	{
		$killmail = KillMail::where('kill_id', $kill_id)->first();

		if (is_null($killmail))
			return response()->json(['msg' => sprintf('Unable to retried killmail %s', $kill_id)], 404);

		$data = [];

		foreach ($killmail->type->insurances as $insurance) {

			array_push($data, [
				'name' => $insurance->name,
				'cost' => $insurance->cost,
				'payout' => $insurance->payout,
				'refunded' => $insurance->refunded(),
				'remaining' => $insurance->remaining($killmail),
			]);

		}

		return response()->json($data);
	}

	public function getPing($kill_id)
	{
		$killmail = KillMail::find($kill_id);

		if (is_null($killmail))
			return response()->json(['msg' => sprintf('Unable to retrieve kill %s', $kill_id)], 404);

		if (!is_null($killmail->ping()))
			return response()->json($killmail->ping());

		return response()->json(['msg' => sprintf('There are no ping information related to kill %s', $kill_id)], 204);
	}

    /**
     * Route: GET /srp/export
     */
	public function showExportPage()
    {
        return view('srp::export');
    }

    /**
     * Route: GET /srp/export-execl?startDate={startDate}&endDate={$endDate}
     */
    public function exportExecl(Request $request)
    {
        try {
            $this->validate($request, [
                'startDate' => 'required',
                'endDate' => 'required',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'error' => $e->errors(),
            ])->setStatusCode(422);
        }
        $mails = KillMail::where([
            ['created_at', '>', $request->startDate],
            ['created_at', '<', $request->endDate],
        ])->whereIn('approved', [1, 2])->get();
        $data = [];
        /** @var KillMail $mail */
        foreach ($mails as $mail) {
            // If there is a ship type that does not meet the regulations,
            // an error message will be returned immediately.
            if (!self::validateShipType($mail->ship_type)) {
                return response()->json([
                    'status' => 'error',
                    'error' => "{$mail->ship_type} is not a valid ship type",
                ])->setStatusCode(200);
            }
            if (!isset($data[$mail->character_name])) {
                $data[$mail->character_name] = [
                    'corpName' => $mail->corp_name,
                    'cost' => 0,
                ];
            }
            $data[$mail->character_name]['cost'] += ShipCost::where('ship', $mail->ship_type)->first()->cost;
        }

        // Logging the export action
        SrpActionLog::logExportAction(auth()->user(), new \DateTime($request->startDate), new \DateTime($request->endDate));

        $path = self::saveAsExecl($data);
        return response()->json([
            'status' => 'ok',
            'url' => str_replace(realpath(sys_get_temp_dir()) . '/', null, $path),
        ]);
    }

    /**
     * Route: GET /srp/export-execl/download/{path}
     */
    public function downloadExecl(string $path)
    {
        return response()->download(sys_get_temp_dir() . "/$path", "$path.xls");
    }

    /**
     * Route: GET /srp/mark-paid?startDate={startDate}&endDate={$endDate}
     */
    public function markPaid(Request $request)
    {
        KillMail::where([
            ['created_at', '>', $request->startDate],
            ['created_at', '<', $request->endDate],
            ['approved', 1],
        ])->update(['approved' => 2]);
        SrpActionLog::logMarkPaidAction(auth()->user(), new \DateTime($request->startDate), new \DateTime($request->endDate));
        return back()->with('markPaidDone', true);
    }

    /**
     * Route: GET /srp/action-history
     */
    public function getActionHistory()
    {
        return ActionLog::collection(SrpActionLog::all());
    }

    /**
     * Check whether the ship type of KM meets the requirements
     * @param string $shipType
     * @return bool
     */
    private static function validateShipType(string $shipType) : bool
    {
        return ShipCost::where('ship', $shipType)->first() !== null;
    }

    private static function saveAsExecl(array $data) : string
    {
        $spreadsheet = new Spreadsheet();
        # Sheet 1 @{
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('按角色');
        // Set headers
        $sheet->setCellValue('A1', 'corp');
        $sheet->setCellValue('B1', 'character_name');
        $sheet->setCellValue('C1', 'cost');

        $i = 1;
        foreach ($data as $name => $value) {
            $i++;
            $sheet->setCellValue("A$i", $value['corpName']);
            $sheet->setCellValue("B$i", $name);
            $sheet->setCellValue("C$i", $value['cost']);
        }
        # @}
        # Sheet 2 @{
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('按军团');

        $corpPaidCost = [];
        foreach ($data as $value) {
            if (!isset($corpPaidCost[$value['corpName']])) {
                $corpPaidCost[$value['corpName']] = 0;
            }
            $corpPaidCost[$value['corpName']] += $value['cost'];
        }
        $i = 0;
        foreach ($corpPaidCost as $corpName => $cost) {
            $i++;
            $sheet->setCellValue("A$i", $corpName);
            $sheet->setCellValue("B$i", $cost);
        }
        # @}
        $writer = new Xls($spreadsheet);
        $tempFilePath = tempnam(sys_get_temp_dir(), 'seat-srp');
        $writer->save($tempFilePath);
        return $tempFilePath;
    }

    private function srpPopulateSlots(stdClass $killMail) : array
    {
        $priceList = [];
        $slots = [
            'killId' => 0,
            'price' => 0.0,
            'shipType' => null,
            'characterName' => null,
            'cargo' => [],
            'dronebay' => [],
        ];

        foreach ($killMail->victim->items as $item) {
            $searchedItem = InvType::find($item->item_type_id);
            $slotName = InvFlag::find($item->flag);
			if (!is_object($searchedItem)) {
			} else {
	            array_push($priceList, $searchedItem->typeName);

            	switch ($slotName->flagName)
            	{
        	        case 'Cargo':
    	                $slots['cargo'][$searchedItem->typeID]['name'] = $searchedItem->typeName;
	                    if (!isset($slots['cargo'][$searchedItem->typeID]['qty']))
                	        $slots['cargo'][$searchedItem->typeID]['qty'] = 0;
            	        if (property_exists($item, 'quantity_destroyed'))
        	                $slots['cargo'][$searchedItem->typeID]['qty'] = $item->quantity_destroyed;
    	                if (property_exists($item, 'quantity_dropped'))
	                        $slots['cargo'][$searchedItem->typeID]['qty'] += $item->quantity_dropped;
                	    break;
            	    case 'DroneBay':
        	            $slots['dronebay'][$searchedItem->typeID]['name'] = $searchedItem->typeName;
    	                if (!isset($slots['dronebay'][$searchedItem->typeID]['qty']))
	                        $slots['dronebay'][$searchedItem->typeID]['qty'] = 0;
                    	if (property_exists($item, 'quantity_destroyed'))
                	        $slots['dronebay'][$searchedItem->typeID]['qty'] = $item->quantity_destroyed;
            	        if (property_exists($item, 'quantity_dropped'))
        	                $slots['dronebay'][$searchedItem->typeID]['qty'] += $item->quantity_dropped;
    	                break;
	                default:
                	    if (!(preg_match('/(Charge|Script|[SML])$/', $searchedItem->typeName))) {
            	            $slots[$slotName->flagName]['id'] = $searchedItem->typeID;
        	                $slots[$slotName->flagName]['name'] = $searchedItem->typeName;
    	                    if (!isset($slots[$slotName->flagName]['qty']))
	                            $slots[$slotName->flagName]['qty'] = 0;
                        	if (property_exists($item, 'quantity_destroyed'))
                    	        $slots[$slotName->flagName]['qty'] = $item->quantity_destroyed;
                	        if (property_exists($item, 'quantity_dropped'))
            	                $slots[$slotName->flagName]['qty'] += $item->quantity_dropped;
        	            }
   	                break;
	            }
            }
        }

        $searchedItem = InvType::find($killMail->victim->ship_type_id);
        $slots['typeId'] = $killMail->victim->ship_type_id;
        $slots['shipType'] = $searchedItem->typeName;
        array_push($priceList, $searchedItem->typeName);
        $prices = $this->srpGetPrice($priceList);

        $slots['characterName'] = $this->getCharacterName($killMail->victim->character_id);

        $slots['killId'] = $killMail->killmail_id;
        $slots['price'] = $prices->appraisal->totals->sell;

        return $slots;
    }

    private function srpGetPrice(array $priceList) : stdClass
    {

        $partsList = implode("\n", $priceList);
        
        $response = (new Client())
            ->request('POST', 'http://evepraisal.com/appraisal.json?market=jita', [
                'multipart' => [
                    [
                        'name' => 'uploadappraisal',
                        'contents' => $partsList,
                        'filename' => 'notme',
                        'headers' => [
                            'Content-Type' => 'text/plain'
                        ]
                    ],
                ]
            ]);

        return json_decode($response->getBody()->getContents());
    }

    /**
     * Try to get the character name from database or ESI.
     *
     * @param int $characterId A character ID
     * @return string The character name
     * @throws \Seat\Eseye\Exceptions\EsiScopeAccessDeniedException
     * @throws \Seat\Eseye\Exceptions\InvalidAuthenticationException
     * @throws \Seat\Eseye\Exceptions\InvalidContainerDataException
     * @throws \Seat\Eseye\Exceptions\RequestFailedException
     * @throws \Seat\Eseye\Exceptions\UriDataMissingException
     */
    private function getCharacterName(int $characterId) : string
    {
        $info = CharacterInfo::find($characterId);
        if ($info !== null) {
            return $info->name;
        }
        /** @var Eseye $client */
        $client = app('esi-client')->get();
        $resp = $client->setVersion('v4')->invoke('get', "/characters/$characterId/");

        CharacterInfo::firstOrCreate(['character_id' => $characterId], [
	        'name'         => $resp->name,
	        'birthday'     => $resp->birthday,
	        'gender'       => $resp->gender,
	        'race_id'      => $resp->race_id,
	        'bloodline_id' => $resp->bloodline_id,
        ]);
        return $resp->name;
    }
}
