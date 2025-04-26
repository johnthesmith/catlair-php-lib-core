<?php
/*
    Catlair PHP Copyright (C) 2021 https://itserv.ru

    This program (or part of program) is free software: you can redistribute
    it and/or modify it under the terms of the GNU Aferro General
    Public License as published by the Free Software Foundation,
    either version 3 of the License, or (at your option) any later version.

    This program (or part of program) is distributed in the hope that
    it will be useful, but WITHOUT ANY WARRANTY; without even the implied
    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the GNU Aferro General Public License for more details.
    You should have received a copy of the GNU Aferror General Public License
    along with this program. If not, see <https://www.gnu.org/licenses/>.
*/



/*
    Miscellaneous ip utilities
*/

namespace catlair;



/*
    Check ip addres by ip range
*/
function ip4Range
(
    $aIp,   /* Checing ipv4 adress for example 127.0.0.1 */
    $aRange /* ip Subnet/mask for example 127.0.0.0/16, or ip */
) : bool
{
    $range = explode( '/', $aRange);
    switch( count( $range ))
    {
        case 0: return false;
        case 1: return $aIp == $aRange;
        default:
            $range_start = ip2long( $range[ 0 ]);
            $range_end  = $range_start + pow(2, 32 - intval( $range[1] )) - 1;
            $ip = ip2long( $aIp );
            return $ip >= $range_start && $ip <= $range_end;
    }
}
