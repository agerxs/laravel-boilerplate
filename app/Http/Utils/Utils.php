<?php


namespace App\Http\Utils;


use App\Models\Location;

class Utils
{
    const R = 6378137; // Earth’s mean radius in meter

    static function rad($x)
    {
        return $x * M_PI / 180;
    }

    static function getDistance(Location $p1, Location $p2)
    {
        $dLat = self::rad($p2->latitude - $p1->latitude);

        $dLong = self::rad($p2->longitude - $p2->longitude);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(self::rad($p1->latitude)) * cos(self::rad($p2->latitude)) *
            sin($dLong / 2) * sin($dLong / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $d = self::R * $c;
        return $d; // returns the distance in meter
    }
}
