<?php

namespace App\Helper;

use DateTime;


class RandomizeHelper
{

    static function getRandomUserName(): string
    {
        $popularNames = ['anton', 'willi', 'gustav', 'erich', 'hans', 'alfred', 'susi', 'elfriede', 'erika', 'elma', 'babsi', 'ute', 'anna'];
        shuffle($popularNames);

        $randomInt = random_int(0, 2000);
        $randomName = $popularNames[0];

        return $randomName . '_' . $randomInt;
    }


    static function getRandomHashKey(): string
    {

        return uniqid();
    }

}
