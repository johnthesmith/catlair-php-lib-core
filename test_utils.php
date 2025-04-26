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

/*
    Test utilities
    still@itserv.ru
    24.11.2023
*/


require_once( 'utils.php' );




/*
    Создание JSON
*/
function clCreateJson
(
    int $aCountMin  = 1,    /* Минимальное количество элементов */
    int $aCountMax  = 100,  /* Максимальное количество Элементов  */
    int $aMaxDepth  = 5,
    int $aDepth     = 0
)
{
    $Result = [];
    $c = rand( $aCountMin, $aCountMax );
    for( $i = 0; $i < $c; $i ++ )
    {
        if
        (
            rand( 0, $aCountMax - $aCountMin ) == 0 &&
            $aDepth < $aMaxDepth
        )
        {
            $aDepth ++;
            $Result[ clUUID() ] = clCreateJson
            (
                $aCountMin,
                $aCountMax,
                $aMaxDepth,
                $aDepth
            );
        }
        else
        {
            $Result[ clUUID() ] = clUUID();
        }
    }
    return $Result;
}
