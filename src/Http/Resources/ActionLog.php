<?php

namespace Denngarr\Seat\SeatSrp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\Resource;
use Seat\Web\Models\User;

class ActionLog extends Resource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'time' => (new \DateTime($this->created_at))->format('Y-m-d H:i:s'),
            'operator' => $this->character->name,
            'detail' => $this->detail,
        ];
    }
}
