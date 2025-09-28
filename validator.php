<?php

namespace catlair;


require_once 'moment.php';

class Validator
{
    /*
        Дерево кодов стран с масками
    */
    private static array $tree =
    [
        "code" =>
        [
            "1" => [
                "country" => "US/Canada",
                "mask" => "+1 (###) ###-####",
                "code" =>
                [
                    "242" => ["country" => "Bahamas", "mask" => "+1-242-###-####"],
                    "264" => ["country" => "Anguilla", "mask" => "+1-264-###-####"],
                    "345" => ["country" => "Cayman Islands", "mask" => "+1-345-###-####"]
                ]
            ],
            "7" => ["country" => "RU/KZ", "mask" => "+7 (###) ###-##-##"],
            "44" => ["country" => "UK", "mask" => "+44 ## #### ####"],
            "49" => ["country" => "Germany", "mask" => "+49 #### ########"],
            "33" => ["country" => "France", "mask" => "+33 # ## ## ## ##"],
            "39" => ["country" => "Italy", "mask" => "+39 ### #######"],
            "34" => ["country" => "Spain", "mask" => "+34 ### ### ###"],
            "41" => ["country" => "Switzerland", "mask" => "+41 ## ### ## ##"],
            "61" => ["country" => "Australia", "mask" => "+61 # #### ####"],
            "81" => ["country" => "Japan", "mask" => "+81 ## #### ####"],
            "82" => ["country" => "South Korea", "mask" => "+82 ## #### ####"],
            "86" => ["country" => "China", "mask" => "+86 ### #### ####"],
            "380" => ["country" => "Ukraine", "mask" => "+380 ## ### ## ##"],
            "998" => ["country" => "Uzbekistan", "mask" => "+998 ## ### ## ##"],
            "55" => ["country" => "Brazil", "mask" => "+55 ## #####-####"],
            "91" => ["country" => "India", "mask" => "+91 ##########"],
            "52" => ["country" => "Mexico", "mask" => "+52 ## #### ####"],
            "54" => ["country" => "Argentina", "mask" => "+54 ## #### ####"],
            "56" => ["country" => "Chile", "mask" => "+56 9 #### ####"],
            "57" => ["country" => "Colombia", "mask" => "+57 3## #######"],
            "58" => ["country" => "Venezuela", "mask" => "+58 ## #######"],
            "60" => ["country" => "Malaysia", "mask" => "+60 # #### ####"],
            "62" => ["country" => "Indonesia", "mask" => "+62 # #### ####"],
            "63" => ["country" => "Philippines", "mask" => "+63 # #### ####"],
            "64" => ["country" => "New Zealand", "mask" => "+64 # #### ####"],
            "65" => ["country" => "Singapore", "mask" => "+65 #### ####"],
            "66" => ["country" => "Thailand", "mask" => "+66 # #### ####"],
            "84" => ["country" => "Vietnam", "mask" => "+84 #########"],
            "90" => ["country" => "Turkey", "mask" => "+90 ## #### ####"],
            "92" => ["country" => "Pakistan", "mask" => "+92 ## #### ####"],
            "93" => ["country" => "Afghanistan", "mask" => "+93 ## #######"],
            "94" => ["country" => "Sri Lanka", "mask" => "+94 ## #######"],
            "95" => ["country" => "Myanmar", "mask" => "+95 ## #######"],
            "98" => ["country" => "Iran", "mask" => "+98 ## #######"],
            "995" => ["country" => "Georgian", "mask" => "+995 ### ### ###"]
        ]
    ];



    /*
        Метод возвращает маску и страну
    */
    public static function phone
    (
        /* Ввод телефонного номера начинается с + */
        string $input
    )
    : array
    {
        $number = preg_replace('/\D/', '', $input);
        $codes = self::$tree['code'];
        $country = '';
        $mask = '';
        $prefix = '';
        $found = false;

        /* ищем максимально длинный совпадающий код */
        for ($len = 3; $len > 0; $len--)
        {
            $p = substr($number, 0, $len);
            if (isset($codes[$p]))
            {
                $prefix = $p;
                $mask = $codes[$p]['mask'];
                $country = $codes[$p]['country'];

                /* проверяем под-коды для +1 */
                if (isset($codes[$p]['code']))
                {
                    $subcode = substr($number, $len, 3);
                    if (isset($codes[$p]['code'][$subcode]))
                    {
                        $prefix .= $subcode;
                        $mask = $codes[$p]['code'][$subcode]['mask'];
                        $country = $codes[$p]['code'][$subcode]['country'];
                    }
                }

                $found = true;
                break;
            }
        }

        /* если код не найден — вернуть ввод */
        if (!$found)
            return ['country' => '', 'formatted' => $input];

        /* количество символов # в маске */
        $maskCount = substr_count($mask, '#');

        /* подставляем цифры только после кода страны, ограниченные маской */
        $digits = substr($number, strlen($prefix), $maskCount);
        $maskDigits = ltrim($mask, '+');
        $formatted = '+';
        $i = 0;
        for ($j = 0; $j < strlen($maskDigits); $j++)
        {
            if ($maskDigits[$j] === '#')
                $formatted .= $digits[$i++] ?? '';
            else
                $formatted .= $maskDigits[$j];
        }
        $formatted = rtrim($formatted, " -()");

        return ['country' => $country, 'formatted' => $formatted];
    }



    /*
        Валидатор для даты-времени через Moment
    */
    public static function moment
    (
        /* строка из input */
        string $text
    )
    :string
    {
        $m = Moment::create() -> fromText( $text );
        return $m -> isEmpty() ? $text : $m -> toString();
    }
}
