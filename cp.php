<?php
/*
    Catlair PHP Copyright (C) 2021 https://itserv.ru

    This program (or part of program) is free software: you can redistribute it
    and/or modify it under the terms of the GNU Aferro General Public License as
    published by the Free Software Foundation, either version 3 of the License,
    or (at your option) any later version.

    This program (or part of program) is distributed in the hope that it will be
    useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Aferro
    General Public License for more details. You should have received a copy of
    the GNU Aferror General Public License along with this program. If not, see
    <https://www.gnu.org/licenses/>.

    Модуль CP-12 работы с композитными предикатами
    https://github.com/johnthesmith/scraps/blob/main/ru/cp12.md
*/




class Cp
{
/* Все кортежи ключа равны всем кортежам замка */
    const AND_AND_EQUALS         = 1;
    /* SQL-алиас: FULL OUTER JOIN (равенство) */
    const FULL_OUTER_JOIN_EQUALS = self::AND_AND_EQUALS;

    /* Все кортежи ключа равны хотя бы одному кортежу замка */
    const AND_OR_EQUALS          = 2;
    /* SQL-алиас: LEFT JOIN (равенство) */
    const LEFT_JOIN_EQUALS       = self::AND_OR_EQUALS;

    /* Хотя бы один кортеж ключа равен всем кортежам замка */
    const OR_AND_EQUALS          = 3;
    /* SQL-алиас: RIGHT JOIN (равенство) */
    const RIGHT_JOIN_EQUALS      = self::OR_AND_EQUALS;

    /* Хотя бы один кортеж ключа равен хотя бы одному кортежу замка */
    const OR_OR_EQUALS           = 4;
    /* SQL-алиас: SEMI JOIN (равенство) */
    const SEMI_JOIN_EQUALS       = self::OR_OR_EQUALS;

    /* Все кортежи ключа содержатся во всех кортежах замка */
    const AND_AND_CONTAINS       = 5;
    /* SQL-алиас: FULL OUTER JOIN (включение) */
    const FULL_OUTER_JOIN_CONTAINS = self::AND_AND_CONTAINS;

    /* Все кортежи ключа содержатся хотя бы в одном кортеже замка */
    const AND_OR_CONTAINS        = 6;
    /* SQL-алиас: LEFT JOIN (включение) */
    const LEFT_JOIN_CONTAINS     = self::AND_OR_CONTAINS;

    /* Хотя бы один кортеж ключа содержится во всех кортежах замка */
    const OR_AND_CONTAINS        = 7;
    /* SQL-алиас: RIGHT JOIN (включение) */
    const RIGHT_JOIN_CONTAINS    = self::OR_AND_CONTAINS;

    /* Хотя бы один кортеж ключа содержится хотя бы в одном кортеже замка */
    const OR_OR_CONTAINS         = 8;
    /* SQL-алиас: SEMI JOIN (включение) */
    const SEMI_JOIN_CONTAINS     = self::OR_OR_CONTAINS;

    /* Все кортежи ключа пересекаются со всеми кортежами замка */
    const AND_AND_INTERSECTS     = 9;
    /* SQL-алиас: CROSS JOIN (пересечение) */
    const CROSS_JOIN_INTERSECTS  = self::AND_AND_INTERSECTS;

    /* Все кортежи ключа пересекаются хотя бы с одним кортежем замка */
    const AND_OR_INTERSECTS      = 10;
    /* SQL-алиас: LEFT JOIN (пересечение) */
    const LEFT_JOIN_INTERSECTS   = self::AND_OR_INTERSECTS;

    /* Хотя бы один кортеж ключа пересекается со всеми кортежами замка */
    const OR_AND_INTERSECTS      = 11;
    /* SQL-алиас: RIGHT JOIN (пересечение) */
    const RIGHT_JOIN_INTERSECTS  = self::OR_AND_INTERSECTS;

    /* Хотя бы один кортеж ключа пересекается хотя бы с одним кортежем замка */
    const OR_OR_INTERSECTS       = 12;
    /* SQL-алиас: SEMI JOIN (пересечение) */
    const SEMI_JOIN_INTERSECTS   = self::OR_OR_INTERSECTS;



    private static array $alg =
    [
        self::AND_AND_EQUALS         => 'and-and-equals',
        self::AND_OR_EQUALS          => 'and-or-equals',
        self::OR_AND_EQUALS          => 'or-and-equals',
        self::OR_OR_EQUALS           => 'or-or-equals',
        self::AND_AND_CONTAINS       => 'and-and-contains',
        self::AND_OR_CONTAINS        => 'and-or-contains',
        self::OR_AND_CONTAINS        => 'or-and-contains',
        self::OR_OR_CONTAINS         => 'or-or-contains',
        self::AND_AND_INTERSECTS     => 'and-and-intersects',
        self::AND_OR_INTERSECTS      => 'and-or-intersects',
        self::OR_AND_INTERSECTS      => 'or-and-intersects',
        self::OR_OR_INTERSECTS       => 'or-or-intersects',
    ];

    private static array $sql =
    [
        self::AND_AND_EQUALS         => 'full-outer-join-equals',
        self::AND_OR_EQUALS          => 'left-join-equals',
        self::OR_AND_EQUALS          => 'right-join-equals',
        self::OR_OR_EQUALS           => 'semi-join-equals',
        self::AND_AND_CONTAINS       => 'full-outer-join-contains',
        self::AND_OR_CONTAINS        => 'left-join-contains',
        self::OR_AND_CONTAINS        => 'right-join-contains',
        self::OR_OR_CONTAINS         => 'semi-join-contains',
        self::AND_AND_INTERSECTS     => 'cross-join-intersects',
        self::AND_OR_INTERSECTS      => 'left-join-intersects',
        self::OR_AND_INTERSECTS      => 'right-join-intersects',
        self::OR_OR_INTERSECTS       => 'semi-join-intersects',
    ];


    /*
        Функция checkContextRule вызывает проверку по правилу
    */
    public static function check
    (
        /*
            искомый  массивов контекст (массив или строка)
            [ [a], [a,b] ]
            Атрибуты секций должны быть отсортированы a-z
        */
        array $key,
        /*
            массив массивов контекстов (массивы или строки)
            [ [a], [a,b] ]
            Атрибуты секций должны быть отсортированы a-z
        */
        array $lock,
        /* константа правила */
        int $rule,
        /**/
        bool $allowEmptyRecord = false
    ): bool
    {
        if( empty($lock) && empty($key))
            return true;

        if( empty($lock) )
            return $allowEmptyRecord;

        if( empty( $key ))
            return true;

        switch ($rule)
        {
            case self::AND_AND_EQUALS:
                return self::checkAndAndEquals( $key, $lock );
            case self::AND_OR_EQUALS:
                return self::checkAndOrEquals( $key, $lock );
            case self::OR_AND_EQUALS:
                return self::checkOrAndEquals( $key, $lock );
            case self::OR_OR_EQUALS:
                return self::checkOrOrEquals( $key, $lock );
            case self::AND_AND_CONTAINS:
                return self::checkAndAndContains( $key, $lock );
            case self::AND_OR_CONTAINS:
                return self::checkAndOrContains( $key, $lock );
            case self::OR_AND_CONTAINS:
                return self::checkOrAndContains( $key, $lock );
            case self::OR_OR_CONTAINS:
                return self::checkOrOrContains( $key, $lock );
            case self::AND_AND_INTERSECTS:
                return self::checkAndAndIntersects( $key, $lock );
            case self::AND_OR_INTERSECTS:
                return self::checkAndOrIntersects( $key, $lock );
            case self::OR_AND_INTERSECTS:
                return self::checkOrAndIntersects( $key, $lock );
            case self::OR_OR_INTERSECTS:
                return self::checkOrOrIntersects( $key, $lock );
            default:
                return false;
        }
    }



    /*
        Преобразует строковое имя оператора (базовое или SQL-алиас)
        в числовой код константы класса.
    */
    public static function fromString
    (
        /* строковое имя оператора или sql алиас */
        string $s
    )
    /* числовой код оператора, или 0 если не найден */
    : int
    {
        $key = array_search($s, self::alg, true);
        if ($key !== false) {
            return $key;
        }
        $key = array_search($s, self::sql, true);
        return $key !== false ? $key : 0;
    }



    /*
        Преобразует числовой код оператора в строковое имя.
    */
    public static function toString
    (
        /* числовой код оператора (константа класса). */
        int $aValue,
        /* если true, возвращает SQL-алиас; иначе — базовое имя. */
        bool $aNotation = false
    )
    /*
        строковое имя оператора или алиас, либо пустая строка при неизвестном
        коде
    */
    : string
    {
        return $aNotation
            ? ( self::sql[ $aValue ] ?? '')
            : ( self::alg[ $aValue ] ?? '');
    }





    /*
        Проверка AND_AND_EQUALS для ключа и замка

        Все массивы должны быть отсортированы

        Истина:
        $lock = [ ['a','b'], ['c','d'] ];
        $key = [ ['a','b'], ['c','d'] ];

        Ложь:
        $lock = [ ['a','b'], ['c','d'] ];
        $key = [ ['a','b'], ['c','e'] ];
    */
    private static function checkAndAndEquals
    (
        array $key,
        array $lock
    ): bool
    {
        return $lock === $key;
    }



    /*
        Проверка AND_OR_EQUALS для ключа и замка
        Для каждого кортежа из record должен быть хотя бы один равный в key

        Истина:
        $lock = [ ['a','b'], ['c','d'], ['e','f']];
        $key = [ ['a','b'], ['c','d'] ];

        Ложь:
        $lock = [ ['a','b'], ['c','d']];
        $key = [ ['a','b'], ['c','d'], ['e','f']];
    */
    private static function checkAndOrEquals
    (
        array $key,
        array $lock,
    ): bool
    {
        foreach( $key as $k )
            if( !in_array( $k, $lock, true ))
                return false;
        return true;
    }



    /*
        Проверка OR_AND_EQUALS для ключа и замка
        Есть кортеж ключа, равный всем кортежам замка

        Истина:
        $lock = [ ['a','b'], ['a','b'], ['a','b'] ];
        $key  = [ ['a','b'], ['c','d'] ];

        Ложь:
        $lock = [ ['a','b'], ['c','d'] ];
        $key  = [ ['a','b']];
    */
    private static function checkOrAndEquals
    (
        array $key,
        array $lock
    ): bool
    {
        foreach( $key as $k )
            if
            (
                array_reduce
                (
                    $lock,
                    fn( $acc, $l ) => $acc && $l === $k,
                    true
                )
            ) return true;
        return false;
    }



    /*
        Проверка OR_OR_EQUALS для ключа и замка
        Есть кортеж ключа, равный хотя бы одному кортежу замка

        Истина:
        $lock = [ ['a','b'], ['x','y'], ['z','q'] ];
        $key  = [ ['a','b'], ['c','d'] ];

        Ложь:
        $lock = [ ['m','n'], ['x','y'] ];
        $key  = [ ['a','b'] ];
    */
    private static function checkOrOrEquals
    (
        array $key,
        array $lock
    ): bool
    {
        foreach( $key as $k )
            if( in_array( $k, $lock, true ))
                return true;
        return false;
    }



    /*
        Проверка AND_AND_CONTAINS для ключа и замка
        Все кортежи ключа полностью содержатся во всех кортежах замка

        Истина:
        $lock = [ ['a','c' ], ['a','c','x'], ['a','b','c','y'] ];
        $key  = [ ['a','c'], ['a'] ];

        Ложь:
        $lock = [ ['a','c'], ['b','x'] ];
        $key  = [ ['a','b'] ];
    */
    private static function checkAndAndContains
    (
        array $key,
        array $lock,
    ): bool
    {
       foreach( $key as $k )
            foreach( $lock as $l )
                if( array_diff( $k, $l ))
                    return false;

        return true;
    }



    /*
        Проверка AND_OR_CONTAINS для ключа и замка
        Есть кортеж ключа, который содержится во всех кортежах замка

        Истина:
        $lock = [ ['a','c'], ['a','c','x'], ['a','b','c','y'] ];
        $key  = [ ['a','c'], ['x'] ];

        Ложь:
        $lock = [ ['a','b'], ['b','x'] ];
        $key  = [ ['a','b'] ];
    */
    private static function checkAndOrContains
    (
        array $key,
        array $lock
    ): bool
    {
        foreach( $key as $k )
            if( !array_filter( $lock, fn( $l ) => array_diff( $k, $l )))
                return true;
        return false;
    }



    /*
        Проверка OR_OR_CONTAINS для ключа и замка
        Хотя бы один кортеж ключа содержится хотя бы в одном кортеже замка

        Истина:
        $key  = [ ['a','c'], ['x'] ];
        $lock = [ ['a','c'], ['a','c','x'], ['a','b','c','y'] ];

        Ложь:
        $key  = [ ['z'] ];
        $lock = [ ['a','b'], ['b','x'] ];

    */
    private static function checkOrOrContains
    (
        array $key,
        array $lock
    ): bool
    {
        foreach( $key as $k )
        {
            foreach( $lock as $l )
            {
                if ( !array_diff( $k, $l ) )
                {
                    return true;
                }
            }
        }
        return false;
    }



    /*
        Проверка AND_AND_INTERSECTS для ключа и замка
        Каждый кортеж ключа пересекается с каждым кортежем замка

        Истина:
        $key  = [ ['a'], ['c'] ];
        $lock = [ ['a','c'], ['a','c','x'] ];

        Ложь:
        $key  = [ ['c'], ['y'] ];
        $lock = [ ['a','b'], ['b','x'] ];
    */
    private static function checkAndAndIntersects
    (
        array $key,
        array $lock
    ): bool
    {
        foreach( $key as $k )
            foreach( $lock as $l )
                if( !array_intersect( $k, $l ) )
                    return false;
        return true;
    }



    /*
        Проверка AND_OR_INTERSECTS для ключа и замка
        Каждый кортеж ключа пересекается хотя бы с одним кортежем замка

        Истина:
        $key  = [ ['a','x'], ['d','y'] ];
        $lock = [ ['a','b'], ['c','d'] ];

        Ложь:
        $key  = [ ['x','y'], ['z'] ];
        $lock = [ ['a','b'], ['c','d'] ];
    */
    private static function checkAndOrIntersects
    (
        array $key,
        array $lock
    ): bool
    {
        foreach( $key as $k )
            if( !array_filter( $lock, fn( $l ) => count(array_intersect( $k, $l ))))
                return false;
        return true;
    }



    /*
        Проверка OR_AND_INTERSECTS для ключа и замка
        Есть кортеж замка, пересекающийся со всеми кортежами ключа

        Истина:
        $key  = [ ['a'], ['c'] ];
        $lock = [ ['a','c'], ['x','y'] ];

        Ложь:
        $key  = [ ['a'], ['c'] ];
        $lock = [ ['a','b'], ['x','y'] ];
    */
    private static function checkOrAndIntersects
    (
        array $key,
        array $lock
    ): bool
    {
        foreach( $lock as $l )
            if ( !array_filter( $key, fn( $k ) => count(array_intersect( $k, $l ))))
                return false;
        return true;
    }




    /*
        Проверка OR_OR_INTERSECTS для ключа и замка
        Есть кортеж ключа, пересекающийся хотя бы с одним кортежем замка

        Истина:
        $key  = [ ['x','a'], ['z'] ];
        $lock = [ ['a','b'], ['c','d'] ];

        Ложь:
        $key  = [ ['x'], ['z'] ];
        $lock = [ ['a','b'], ['c','d'] ];
    */
    private static function checkOrOrIntersects
    (
        array $key,
        array $lock
    ): bool
    {
        foreach( $key as $k )
            foreach( $lock as $l )
                if( count( array_intersect( $k, $l )) > 0)
                    return true;
        return false;
    }


}
