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

/*
    Fast functions and utils for Moment from moment.php
    Fork from catlair utilites

    still@itserv.ru
    chiv@itserv.ru
*/

namespace catlair;



include_once 'moment.php';


define( 'DELTA_DECIMAL', 'DELTA_DECIMAL' );     /* 1.5 Min */
define( 'DELTA_TIME',    'DELTA_TIME' );        /* 01:30 */
define( 'DELTA_NAME',    'DELTA_NAME' );        /* 1 Min 30 Sec */


function clNow( $Timezone = 0 )
{
    $Now = new Moment();
    $Now -> SetTimezone( $Timezone );
    return $Now -> Now();
}



function clNowToString( $AFormat = null, $Timezone = 0 )
{
    $Now = new Moment();
    $Now -> SetTimezone( $Timezone );
    $Now -> Now();
    return $Now -> ToString( $AFormat );
}



function clDateToUTC ( $AData, $Timezone = 0, $AFormat = null)
{
    $Now = new Moment();
    return $Now -> SetTimezone( $Timezone ) -> FromString ($AData) -> SetTimezone( 0 ) -> ToString($AFormat);
}



function clUTCToDate ( $AData, $Timezone = 0, $AFormat = null )
{
    $Now = new Moment();
    return $Now -> FromString ($AData) -> SetTimezone( $Timezone ) -> ToString($AFormat);
}




/*
    Finding the momont in text and return it as ODBC momnet string
*/
function clODBCFromText
(
    $AText,     /* The source text */
    $AFormat    /* Recomended format */
)
{
    /* Defining the result */
    $Result = '';

    /*  Regexp variables */
    $YL     = '(\d{4})';
    $TD     = '(\d\d)';
    $S      = '[\\.\\,\\-\\:\\\\\\ \\/]';
    $SDT    = '\D+?';
    $B      = '\D*?';
    $AMPM   = '([AP]M)';

    if( !empty( $AFormat ))
    {
        /* Building the masq from format like "dmYHis" */
        $Masq = preg_replace( '/' . $S . '/', '', $AFormat );

        /* Defining indexes in format masq */
        $IYar = strpos( $Masq, 'Y' );
        $IYar2 = strpos( $Masq, 'y' );
        $IMon = strpos( $Masq, 'm' );
        $IDay = strpos( $Masq, 'd' );
        $IHow = strpos( $Masq, 'H' );
        $IMin = strpos( $Masq, 'i' );
        $ISec = strpos( $Masq, 's' );
        $IAPM = strpos( $Masq, 'a' );

        if( ( $IYar !== false || $IYar2 !== false ) && $IMon !==False && $IDay !== false )
        {
            /* Building regexp from masq */
            $Reg = str_replace
            (
                [ '-', ':', '.', '/', ',', 'd', 'm', 'y', 'Y', 'H', 'i', 's', 'a',  ' ',   '~' ],
                [ '~', '~', '~', '~', '~', $TD, $TD, $TD, $YL, $TD, $TD, $TD, $AMPM, $SDT, $S ],
                $AFormat
            );

            /* matching text with regexp */
            preg_match( '/' . $Reg . '/', $AText, $matches );

            $Result =
            ( $IYar !== false && count( $matches ) > $IYar + 1 ? $matches[ $IYar + 1 ] . '-' : '' ) .
            ( $IYar2 !== false && count( $matches ) > $IYar2 + 1 ? substr(date('Y'),0,2).$matches[ $IYar2 + 1 ] . '-' : '' ) .
            ( $IMon !== false && count( $matches ) > $IMon + 1 ? $matches[ $IMon + 1 ] . '-' : '' ) .
            ( $IDay !== false && count( $matches ) > $IDay + 1 ? $matches[ $IDay + 1 ] . ' ' : '' ) .
            ( $IHow !== false && count( $matches ) > $IHow + 1 ? $matches[ $IHow + 1 ] . ':' : '' ) .
            ( $IMin !== false && count( $matches ) > $IMin + 1 ? $matches[ $IMin + 1 ] . ':' : '' ) .
            ( $ISec !== false && count( $matches ) > $ISec + 1 ? $matches[ $ISec + 1 ]       : '' ) .
            ( $IAPM !== false && count( $matches ) > $IAPM + 1 ? ' ' . $matches[ $IAPM + 1 ] : '' );

            if( $IAPM !== false )
            {
                $DateTime = DateTime :: createFromFormat( 'Y-m-d h:i:s a', $Result );
                $Result = $DateTime !== false ? $DateTime -> format( 'Y-m-d H:i:s' ) : $Result;
            }
        }
    }
    return $Result;
}




/*
    Finding the momont in text and return it as ODBC momnet string
*/
function clMomentStringFromText
(
    $AText,             /* The source text */
    $AFormat = null     /* Recomended format */
)
{
    $Result = '';

    if( !empty( $AFormat )) $Result = clODBCFromText( $AText, $AFormat );
    if( $Result == '') $Result = clODBCFromText( $AText, 'Y-m-d H:i:s' );
    if( $Result == '') $Result = clODBCFromText( $AText, 'd-m-Y H:i:s' );
    if( $Result == '') $Result = clODBCFromText( $AText, 'd-m-y H:i:s' );
    if( $Result == '') $Result = clODBCFromText( $AText, 'Y-m-d' );
    if( $Result == '') $Result = clODBCFromText( $AText, 'd-m-Y' );
    if( $Result == '') $Result = clODBCFromText( $AText, 'd-m-y' );

    return $Result;
}



function clMomentDelta( $ADelta )
{
    return
    [
        'Millisecond' => floor( $ADelta % Moment::SECOND / Moment::MILLISECOND ),
        'Second'     => floor( $ADelta % Moment::MINUTE / Moment::SECOND ),
        'Minute'     => floor( $ADelta % Moment::HOUR / Moment::MINUTE ),
        'Hour'       => floor( $ADelta % Moment::DAY / Moment::HOUR ),
        'Day'        => floor( $ADelta % Moment::MONTH / Moment::DAY ),
        'Month'      => floor( $ADelta % Moment::YEAR / Moment::MONTH ),
        'Year'       => floor( $ADelta / Moment::YEAR )
    ];
}



/*
    Return time scalar in microseconds from string
*/
function clTimeFromString( $AValue )
{
    /* Split the string to part */
    $Lexems = explode( ':', $AValue );

    $Hours  = count( $Lexems ) > 0 ? intval( trim( $Lexems[ 0 ])) : 0;
    $Min    = count( $Lexems ) > 1 ? intval( trim( $Lexems[ 1 ])) : 0;
    $Sec    = count( $Lexems ) > 2 ? intval( trim( $Lexems[ 2 ])) : 0;
    $MSec   = count( $Lexems ) > 3 ? intval( trim( $Lexems[ 3 ])) : 0;

    return $Hours * Moment::HOUR + $Min * Moment::MINUTE + $Sec * Moment::SECOND;
}



/*
    Convert Delata moment in to string
*/
function clMomentDeltaToString
(
    $ADelta,                        /* float Delta mometn */
    $AName      = null,             /* Aray names */
    $AFormat    = DELTA_DECIMAL,
    $AAccuracy  = 0                 /* */
)
{
    /* Define names */
    if ( $AName == null ) $AName = Moment::NAMES;
    $Result = '';

    switch( $AFormat )
    {
        case DELTA_DECIMAL:
            if      ( $ADelta < Moment::MICROSECOND )   $Result =  $AName[ 'Zero' ];
            elseif  ( $ADelta < Moment::MILLISECOND )    $Result =  $ADelta . $AName[ 'Microsecond' ];
            elseif  ( $ADelta < Moment::SECOND )         $Result =  round ($ADelta / Moment::MILLISECOND, $AAccuracy ) . $AName[ 'Millisecond' ];
            elseif  ( $ADelta < Moment::MINUTE )         $Result =  round ($ADelta / Moment::SECOND,      $AAccuracy ) . $AName[ 'Second' ];
            elseif  ( $ADelta < Moment::HOUR )           $Result =  round ($ADelta / Moment::MINUTE,      $AAccuracy ) . $AName[ 'Minute' ];
            elseif  ( $ADelta < Moment::DAY )            $Result =  round ($ADelta / Moment::HOUR,        $AAccuracy ) . $AName[ 'Hour' ];
            elseif  ( $ADelta < Moment::MONTH )          $Result =  round ($ADelta / Moment::DAY,         $AAccuracy ) . $AName[ 'Day' ];
            elseif  ( $ADelta < Moment::YEAR )           $Result =  round ($ADelta / Moment::MONTH,       $AAccuracy ) . $AName[ 'Month' ];
            else                                         $Result =  round ($ADelta / Moment::YEAR,        $AAccuracy ) . $AName[ 'Year' ];
        break;

        case DELTA_TIME:
            if( $ADelta >= Moment::YEAR )        $Result .= round( $ADelta / Moment::YEAR, 0, PHP_ROUND_HALF_DOWN ) . '.';
            if( $ADelta >= Moment::MONTH )       $Result .= round( $ADelta % Moment::YEAR / Moment::MONTH, 0, PHP_ROUND_HALF_DOWN ) . '.';
            if( $ADelta >= Moment::DAY )         $Result .= round( $ADelta % Moment::MONTH / Moment::DAY, 0, PHP_ROUND_HALF_DOWN ) . ' ';
            if( $ADelta >= Moment::HOUR )        $Result .= round( $ADelta % Moment::DAY / Moment::HOUR, 0, PHP_ROUND_HALF_DOWN ) . ':';
            if( $ADelta >= Moment::MINUTE )      $Result .= round( $ADelta % Moment::HOUR / Moment::MINUTE, 0, PHP_ROUND_HALF_DOWN ) . ':';
            if( $ADelta >= Moment::SECOND )      $Result .= round( $ADelta % Moment::MINUTE / Moment::SECOND, 0, PHP_ROUND_HALF_DOWN ) . '.';
            if( $ADelta >= Moment::MILLISECOND ) $Result .= round( $ADelta % Moment::SECOND / Moment::MILLISECOND, 0, PHP_ROUND_HALF_DOWN );
        break;

        case DELTA_NAME:
            $Part = clMomentDelta( $ADelta );
            if( $Part[ 'Year' ] > 0 )       $Result .= $Part[ 'Year' ]          . ' ' . $AName[ 'Year' ] . ' ';
            if( $Part[ 'Month' ] > 0 )      $Result .= $Part[ 'Month' ]         . ' ' . $AName[ 'Month' ] . ' ';
            if( $Part[ 'Day' ] > 0 )        $Result .= $Part[ 'Day' ]           . ' ' . $AName[ 'Day' ] . ' ';
            if( $Part[ 'Hour' ] > 0 )       $Result .= $Part[ 'Hour' ]          . ' ' . $AName[ 'Hour' ] . ' ';
            if( $Part[ 'Minute' ] > 0 )     $Result .= $Part[ 'Minute' ]        . ' ' . $AName[ 'Minute' ] . ' ';
            if( $Part[ 'Second' ] > 0 )     $Result .= $Part[ 'Second' ]        . ' ' . $AName[ 'Second' ] . ' ';
            if( $Part[ 'Millisecond' ] > 0 ) $Result .= $Part[ 'Millisecond' ]    . ' ' . $AName[ 'Millisecond' ];
        break;
    }

    return $Result;
}
