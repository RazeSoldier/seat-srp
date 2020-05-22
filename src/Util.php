<?php

namespace Denngarr\Seat\SeatSrp;

use Illuminate\Support\Facades\Cache;
use RazeSoldier\EveTranslate\ItemTranslate;

class Util
{
    public static function translateShipType(string $shipType, string $targetLang) : string
    {
        if ($targetLang === 'en') {
            return $shipType;
        }
        return Cache::rememberForever("ship-$shipType-$targetLang", function () use ($shipType, $targetLang) {
            return ItemTranslate::translate($shipType, $targetLang);
        });
    }
}