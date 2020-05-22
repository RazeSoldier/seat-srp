<?PHP

namespace Denngarr\Seat\SeatSrp\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use RazeSoldier\EveTranslate\ItemTranslate;
use Seat\Services\Settings\Profile;
use Seat\Web\Http\Controllers\Controller;
use Denngarr\Seat\SeatSrp\Models\KillMail;


class SrpAdminController extends Controller {

    public function srpGetKillMails()
    {
        $killmails = KillMail::where('approved','>','-2')->orderby('created_at', 'desc')->get();
        $lang = Profile::get('language');
        if ($lang === 'cn') {
            $lang = 'zh';
        } else {
            $lang = 'en';
        }
        foreach ($killmails as $killmail) {
            $killmail->ship_type = $this->translateShipType($killmail->ship_type, $lang);
        }
        return view('srp::list', compact('killmails'));
    }

    public function srpApprove($kill_id, $action) {
        $killmail = KillMail::find($kill_id);

        switch ($action)
        {
            case 'Approve':
                $killmail->approved = '1';
                break;
            case 'Reject':
                $killmail->approved = '-1';
                break;
            case 'Paid Out':
                $killmail->approved = '2';
                break;
            case 'Pending':
                $killmail->approved = '0';
                break;
        }

        $killmail->approver = auth()->user()->name;
        $killmail->save();
        
        return json_encode(['name' => $action, 'value' => $kill_id, 'approver' => auth()->user()->name]);
    }

    private function translateShipType(string $shipType, string $targetLang) : string
    {
        if ($targetLang === 'en') {
            return $shipType;
        }
        return Cache::rememberForever("ship-$shipType-$targetLang", function () use ($shipType, $targetLang) {
            return ItemTranslate::translate($shipType, $targetLang);
        });
    }
}
