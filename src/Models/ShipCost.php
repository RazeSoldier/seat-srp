<?php

namespace Denngarr\Seat\SeatSrp\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string ship The ship type
 * @property double cost The ship cost
 */
class ShipCost extends Model
{
    protected $table = 'srp_ship_cost';
}
