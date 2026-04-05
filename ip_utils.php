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
    /* Checing ipv4 adress for example 127.0.0.1 */
    string $aIp,
    /* ip Subnet/mask for example 127.0.0.0/16, or ip */
    string $aRange
)
: bool
{
    $range = explode( '/', $aRange );
    $result = false;
    if( count($range) === 1 )
    {
        $result = $aIp === $aRange;
    }
    else
    {
        $subnet = ip2long($range[0]);
        $mask = (int)$range[1];
        $ip = ip2long($aIp);
        /* Маска в битовом представлении */
        $maskLong = -1 << (32 - $mask);

        $result = ($ip & $maskLong) === ($subnet & $maskLong);
    }
    return $result;
}
