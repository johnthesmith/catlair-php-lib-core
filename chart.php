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
*/



namespace catlair;



/* Includ libraryes */
require_once 'text.php';



class Chart extends Text
{
    private $Line = [];



    public static function create()
    {
        return new Chart();
    }



    public function bar
    (
        $AFillPercent,
        $ALength        = 0,
        $AAligment      = STR_PAD_RIGHT,  /* Aligment Type like str_type pad_type argument */
        $ACeil          = true,
        $AEmpty         = ' ',
        $AFill          = '#'
    )
    {
        $s = $ALength * $AFillPercent;
        $this -> add
        (
            strPad
            (
                str_repeat
                (
                    $AFill,
                    $ACeil ? ceil( $s ) : floor( $s )
                ),
                $ALength,
                $AEmpty,
                $AAligment
            )
        );

        return $this;
    }



    public function text
    (
        $AValue,
        $ALength        = 0,
        $AAligment      = STR_PAD_RIGHT,  /* Aligment Type like str_type pad_type argument */
        $AEmpty         = ' '
    )
    {
        return $this -> add
        (
            strPad
            (
                $AValue,
                $ALength,
                $AEmpty,
                $AAligment
            )
        );
    }
}
