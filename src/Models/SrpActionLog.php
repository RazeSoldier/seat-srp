<?php

namespace Denngarr\Seat\SeatSrp\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Seat\Web\Models\User;

/**
 * @property int operator_id
 * @property string action_type
 * @property string detail
 * @property string created_at
 */
class SrpActionLog extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public static function logExportAction(User $user, DateTimeInterface $startDate, DateTimeInterface $endDate)
    {
        $log = new self;
        $log->operator_id = $user->id;
        $log->action_type = 'export';
        $log->detail = '导出' . $startDate->format('Y-m-d') . ' - ' . $endDate->format('Y-m-d')
            . '期间所有的“已审核”和“已补损”的SRP请求';
        $log->save();
    }

    public static function logMarkPaidAction(User $user, DateTimeInterface $startDate, DateTimeInterface $endDate)
    {
        $log = new self;
        $log->operator_id = $user->id;
        $log->action_type = 'mark_paid';
        $log->detail = '批量标记' . $startDate->format('Y-m-d') . ' - ' . $endDate->format('Y-m-d')
            . '期间所有的“已审核”的SRP请求为“已补损”状态';
        $log->save();
    }
}
