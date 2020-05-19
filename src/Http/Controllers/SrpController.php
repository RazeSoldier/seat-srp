<?PHP

namespace Denngarr\Seat\SeatSrp\Http\Controllers;

use Denngarr\Seat\SeatSrp\Models\Sde\InvFlag;
use Denngarr\Seat\SeatSrp\Models\Sde\InvType;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Seat\Eseye\Eseye;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Seat\Web\Http\Controllers\Controller;
use Seat\Eveapi\Models\Character\CharacterInfo;
use Denngarr\Seat\SeatSrp\Models\KillMail;
use Denngarr\Seat\SeatSrp\Validation\AddKillMail;
use stdClass;


class SrpController extends Controller {

    public function srpGetRequests()
    {
        $kills = KillMail::where('user_id', auth()->user()->id)
                         ->orderby('created_at', 'desc')
                         ->take(20)
                         ->get();

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
        $corpId = CharacterInfo::where('character_id', auth()->user()->id)->first()->corporation_id;
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

        CharacterInfo::firstOrNew(['character_id' => $characterId])->fill([
            'name'            => $resp->name,
            'description'     => $resp->optional('description'),
            'corporation_id'  => $resp->corporation_id,
            'alliance_id'     => $resp->optional('alliance_id'),
            'birthday'        => $resp->birthday,
            'gender'          => $resp->gender,
            'race_id'         => $resp->race_id,
            'bloodline_id'    => $resp->bloodline_id,
            'ancestry_id'    => $resp->optional('ancestry_id'),
            'security_status' => $resp->optional('security_status'),
            'faction_id'      => $resp->optional('faction_id'),
        ])->save();
        return $resp->name;
    }
}
