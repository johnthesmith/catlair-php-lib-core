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
    Target:

        Main module for works with date & time moments.

    For example return current DateTime in string format:

        Moment :: Create()
        -> Now()
        -> Inc( self::DAY )
        -> ToString();

    Code style:

        Class name:         TClassName
        Variables:          VariableName
        Property:           PropertyName
        Methods:            MethodName
        Non obfurs char:    ""
        Obfurs char:        ''

    Coders:

        still, chiv
*/

namespace catlair;



require_once 'moment_utils.php';


/* Constructor */
class Moment
{
    /*
        Constants time interval
    */
    const EMPTY         = 0;

    const MICROSECOND   = 1;
    const MILLISECOND   = self::MICROSECOND * 1000;
    const SECOND        = self::MILLISECOND * 1000;
    const MINUTE        = self::SECOND * 60;
    const HOUR          = self::MINUTE * 60;
    const DAY           = self::HOUR * 24;
    const MONTH         = self::DAY * 30.5;
    const YEAR          = self::DAY * 365;


    /* Constants result of comparing */
    const MORE          = 1;
    const LESS          = -1;
    const EQUAL         = 0;


    const ODBC_FORMAT           = 'Y-m-d H:i:s';    /* ODBC canonical */
    const ODBC_MLS_FORMAT       = 'Y-m-d H:i:s.u';  /* ODBC with milliseconds */
    const ODBC_FORMAT_POINT     = 'Y.m.d H:i:s';    /* ODBC canonical */
    const ISO_8601_FORMAT       = 'Y-m-d\TH:i:s';   /* ISO_8601 canonical */
    const DATE_FORMAT           = 'Y-m-d';          /* ODBC date */
    const DATE_SEARCH_FORMAT    = 'Ymd';            /* moment to search key */

    const NAMES =
    [
        'Zero'          => 'zero',
        'Microsecond'   => 'mks',
        'Millisecond'   => 'ms',
        'Second'        => 'sec',
        'Minute'        => 'min',
        'Hour'          => 'hour',
        'Day'           => 'day',
        'Month'         => 'month',
        'Year'          => 'year',
        'Inf'           => 'inf'
    ];

    private $Moment         = self::EMPTY;          /* UTC Absolute moment in microseconds */
    private $TimezoneShift  = 0;                    /* Time shift for current timezone in microseconds */
    private $Format         = Moment::ODBC_FORMAT; /* Format for convert to string */



    /*
        Constructor
        $AString - date-time string in ymd-hms or other format
    */
    function __construct( $AString = null, $ATimezone = 0, $AUTC = false  )
    {
        $this -> SetTimezone( $ATimezone );
        if ( $AString == null ) $this -> SetEmpty();
        else $this -> FromString ( $AString, $AUTC );
    }



    /*
        Create and return a new moment
    */
    static function &Create
    (
        $AString = null, $ATimezone = 0, $AUTC = false
    )
    {
        $Result = new Moment( $AString, $ATimezone, $AUTC );
        return $Result;
    }



    /*
        This moment return current value
    */
    public function Get( $AUTC = false )
    {
        return $this -> IsEmpty()
        ? $this -> Moment
        : ( $this -> Moment + (( $AUTC ) ? self::EMPTY : $this -> TimezoneShift ));
    }



    /*
        This moment set to value
    */
    public function &Set( $AValue, $AUTC = false )
    {
        $this -> Moment
        = empty( $AValue ) || $AValue == self::EMPTY
        ? self::EMPTY
        : ((float) $AValue - (( $AUTC ) ? self::EMPTY : $this -> TimezoneShift ));
        return $this;
    }



    /*
        Return timezone in hours +-
    */
    public function GetTimezone()
    {
        return (int) $this -> TimezoneShift / self::HOUR;
    }



    /*
        Set timezone in hours +-
    */
    public function &SetTimezone( $AValue )
    {
        $this -> TimezoneShift = (float) $AValue * self::HOUR;
        return $this;
    }



    /*
        Check empty date
        Third palce using $Moment
    */
    public function IsEmpty()
    {
        return $this -> Moment == self::EMPTY;
    }



    /*
        Check empty date
    */
    public function &SetEmpty()
    {
        $this -> Set( self::EMPTY );
        return $this;
    }



    /*
        Add Interval to current Moment
    */
    public function &Add( $AInterval )
    {
        $this -> Moment += $AInterval;
        return $this;
    }



    /*
        Decrease this moment
    */
    public function &Dec( $AInterval )
    {
        $this -> Moment -= $AInterval;
        return $this;
    }



    /*
        This moment compare with $AMoment
        Return MORE, LESS, EQUAL
    */
    public function Compare( $AMoment )
    {
        if     ( $this -> Get( true ) > $AMoment -> Get( true ) ) return self::MORE;
        elseif ( $this -> Get( true ) < $AMoment -> Get( true ) ) return self::LESS;
        else return self::EQUAL;
    }



    /*
        Object compare with now
        Return MORE, LESS, EQUAL
    */
    public function CompareNow()
    {
        return $this -> Compare( $this -> Clone() -> Now() );
    }



    public function NowIfEmpty()
    {
        if ( $this -> IsEmpty() ) $this -> Now();
        return $this;
    }



    public function NowOrLess()
    {
        if ( $this -> CompareNow() == self::MORE ) $this -> Now();
        return $this;
    }



    /*
        Return delta between two Moments
    */
    public function Delta
    (
        $AMoment,                       /* Less moment */
        $AScale = self::MICROSECOND     /* Scale unit */
    )
    {
        return ( $this -> Get() - $AMoment -> Get() ) / $AScale;
    }




    /*
        Object set to GMT now
    */
    public function Now
    (
        bool $AOnlyEmpty = false
    )
    {
        if( $AOnlyEmpty && $this -> isEmpty() || !$AOnlyEmpty )
        {
            $this -> Moment = microtime( true ) * 1000000;
        }
        return $this;
    }



    /*
        Convert from string
    */
    public function FromString
    (
        $AValue,        /* String with Data Time Moment or integer from string with milliseconds */
        $AUTC = false   /* true for UTC, false for Local (default) */
    )
    {
        $this -> SetEmpty();
        if( gettype( $AValue ) == 'string' )
        {
            if( is_numeric( $AValue ))
            {
                $t = ( int ) $AValue / Moment :: MILLISECOND;
            }
            else
            {
                $t = strtotime( str_replace('/', '-', $AValue ));
            }
            $this -> Set( $t * self::SECOND, $AUTC );
        }
        return $this;
    }



    /*
        Convert from string
    */
    public function FromOdbcString
    (
        $AValue /* String with Data Time Moment or integer from string with milliseconds */
    )
    {
        $dt = \DateTime::createFromFormat ( 'Y-m-d H:i:s.u', $AValue );
        if( !empty( $dt ))
        {
            $this -> set( $dt -> format( 'Uu' ));
        }
        return $this;
    }



    public function FromText
    (
        $AText,         /* Any text with Date Time moment */
        $AUTC = false   /* Use UTC for moment */
    )
    {
        $String = clMomentStringFromText( $AText );
        if ( $String != null ) $this -> FromString( $String , $AUTC );
        return $this;
    }


    public function GetDateFormat()
    {
        $Result= $this -> Format;

        $Parts = explode( ' ', $this -> Format );
        if ( count( $Parts > 1 ))
        {
            if      ( strpos( $Parts[0], 'i' )) $Result = $Parts[ 1 ];
            elseif  ( strpos( $Parts[1], 'i' )) $Result = $Parts[ 0 ];
        }
        return $Result;
    }



    public function ToDateString()
    {
        return $this -> ToString( $this -> GetDateFormat() );
    }


    /*
        Convert moment to string with $AFormat
    */
    public function ToString
    (
        $AFormat    = null,     /* Use moment format */
        $ATimezone  = false,    /* Return time zone in format */
        $AUTC       = false,    /* Return UTC */
        $ADefault   = ''
    )
    {
        if ( $this-> IsEmpty() )
        {
            $Result = $ADefault;
        }
        else
        {
            if ( empty( $AFormat ))
            {
                $AFormat = $this -> Format;
            }

            $d = number_format( $this -> Get( $AUTC ) / self::SECOND, 6, '.', '' );
            $Result = date_create_from_format( 'U.u', $d ) -> format( $AFormat);
//            $Result = date( $AFormat, (float) $this -> Get( $AUTC ) / self::SECOND );
            if( $ATimezone )
            {
                $Result .=  ' ' .  $this -> TimezoneToString();
            }
        }
        return $Result;
    }



    public function ToStringODBC( $AUTC = true, $ADefault = '' )
    {
        return $this -> ToString( self::ODBC_FORMAT, false, $AUTC, $ADefault );
    }



    /*
        Convert timezone to string
    */
    public function TimezoneToString()
    {
        $Timezone  = $this -> GetTimeZone();

        if ( $Timezone > 0 ) $Sign = '+';
        elseif ( $Timezone < 0 ) $Sign = '-';
        else $Sign = ' ';

        return '(' . $Sign . abs( $Timezone ) . ')';
    }



    /*
        Set format for convert string
    */
    public function &SetFormat( $AFormat )
    {
        if ( ! empty( $AFormat )) $this -> Format = $AFormat;
        return $this;
    }



    /*
        Set format for convert string
    */
    public function GetFormat()
    {
        return $this -> Format;
    }



    /*
        Return date without time
    */
    public function Date()
    {
        /* TODO Next line mus work but return +2 hours */
        /* return floor( $this -> Get() / self::DAY ) * self::DAY; */

        /* This is bad code */
        $Current =  $this -> Get() / self::SECOND;
        return mktime
        (
            0,
            0,
            0,
            date('n', $Current),
            date('d', $Current),
            date('Y', $Current)
        ) * self::SECOND;

    }



    /*
        Return time only
    */
    public function Time()
    {
        return $this -> Get() - $this -> Date();
    }



    /*
        Cut the time from current moment
    */
    public function TrimTime()
    {
        $this -> SetLastInterval( self::DAY );
        return $this;
    }



    /*

    */
    public function SetLastInterval( $AInterval )
    {
        $this-> Set( floor ( $this -> Get() / $AInterval ) * $AInterval );
        return $this;
    }



    public function SetNextInterval( $AInterval )
    {
        $this-> Set( floor ( $this -> Get() / $AInterval + 1 ) * $AInterval );
        return $this;
    }



    public function GetDayWeekName()
    {
        return date( 'D', $this -> Get() / self::SECOND );
    }



    public function GetDayNumber()
    {
        return date( 'j', $this -> Get() / self::SECOND );
    }



    public function GetHour()
    {
        return date( 'H', $this -> Get() / self::SECOND );
    }



    /*
        Return Minute
    */
    public function GetMinute()
    {
        return (int) date( 'i', $this -> Get() / self::SECOND );
    }



    public function &CopyTo( &$AMoment )
    {
        $AMoment
        -> SetTimezone  ( $this -> GetTimezone())
        -> Set          ( $this -> Get())
        -> SetFormat    ( $this -> GetFormat());
        return $this;
    }



    public function &CopyFrom( &$AMoment )
    {
        $AMoment -> CopyTo( $this );
        return $this;
    }



    /*
        Create and return copy of this Moment object
    */
    public function &Clone()
    {
        return Moment::Create() -> CopyFrom( $this );
    }



    public function &SetDayBegin()
    {
        $Current =  $this -> Get() / self::SECOND;
        $this -> Set
        (
            mktime
            (
                0,
                0,
                0,
                date('n', $Current),
                date('d', $Current),
                date('Y', $Current)
            ) * self::SECOND
        );
        return $this;
    }



    /*
        Set this moment to the end of day. It is the beginning of the next day.
    */
    public function &SetDayEnd()
    {
        return $this -> SetDayBegin() -> Add( self::DAY );
    }



    /*
        Return new Moment with begin of day for this moment.
    */
    public function &GetDayBegin()
    {
        return $this -> Clone() -> SetDayBegin();
    }



    /*
        Return new Moment with end of day for this moment.
    */
    public function &GetDayEnd()
    {
        return $this -> Clone() -> SetDayEnd();
    }



    /*
    */
    public function &SetMonthBegin()
    {
        $Current =  $this -> Get() / self::SECOND;
        $this -> Set
        (
            mktime
            (
                0,
                0,
                0,
                date('n', $Current),
                1,
                date('Y', $Current)
            ) * self::SECOND
        );
        return $this;
    }



    public function &AddMonth( $ACountMonth = 1 )
    {
        $Current =  $this -> Get() / self::SECOND;
        $this -> Set
        (
            mktime
            (
                date('G', $Current),
                date('i', $Current),
                date('s', $Current),
                date('n', $Current) + $ACountMonth,
                date('j', $Current),
                date('Y', $Current)
            ) * self::SECOND
        );
        return $this;
    }



    public function &SetMonthEnd()
    {
        $this -> SetMonthBegin() -> AddMonth();
        return $this;
    }



    public function &GetMonthBegin()
    {
        return $this -> Clone() -> GetMonthBegin();
    }



    public function &GetMonthEnd()
    {
        return $this -> Clone() -> GetMonthEnd();
    }



    /*
        Testing the moment over schedule.
        TODO: It must be move to schedule.php
    */
    public function TestSchedule( $AParams )
    {
        $IntervalsHour = TIntervals::Create();
        for( $i = 1; $i < 5; $i++ )
        {
            $NameOfInterval = $this -> ToString( 'D' ) . 'Interval' . $i;
            $ValueOfInterval = clValueFromObject( $AParams, $NameOfInterval );
            $IntervalsHour -> Add( TInterval::Create() -> FromTimeString( $ValueOfInterval ));
        }

        /* Include intervals gets */
        $Include_0 = TIntervals::Create() -> FromMomentString( clValueFromObject( $AParams, 'IntervalInclude' ));
        $Include_1 = TIntervals::Create() -> FromMomentString( clValueFromObject( $AParams, 'IntervalInclude_1' ));

        /* Exclude intervals gets */
        $Exclude = TIntervals::Create() -> FromMomentString( clValueFromObject( $AParams, 'IntervalExclude' ));

        $Result =
        (
            $IntervalsHour -> TestMoment( $this ) ||
            $Include_0 -> TestMoment( $this ) ||
            $Include_1 -> TestMoment( $this )
        )
        && !$Exclude -> TestMoment( $this );

        /* Check include interval */
        $IntervalIncludeExtra = clValueFromObject( $AParams, 'IntervalInclude_Extra' );
        if( !empty( $IntervalIncludeExtra ))
        {
            $Include_Extra = TIntervals::Create() -> FromMomentString( $IntervalIncludeExtra );
            $Result = $Result || $Include_Extra -> TestMoment( $this );
        }

        /* Check exclude interval */
        $IntervalExcludeExtra = clValueFromObject( $AParams, 'IntervalExclude_Extra' );
        if( !empty( $IntervalExcludeExtra ))
        {
            $Exclude_Extra = TIntervals::Create() -> FromMomentString( $IntervalExcludeExtra );
            $Result = $Result && ! $Exclude_Extra -> TestMoment( $this );
        }

        return $Result;

    }



    public function &CorrectByTimezone()
    {
        $this -> Set($this -> Get(true), false);
        return $this;
    }



    public function &MinMax( $AMoments, $TypeCompare )
    {
        foreach( $AMoments as $Moment )
        {
            if( !$Moment -> isEmpty())
            {
                if ( $this -> IsEmpty() ) $this -> CopyFrom($Moment);
                else
                {
                    if ($this -> Compare($Moment) == $TypeCompare) $this -> CopyFrom($Moment);
                }
            }
        }
        return $this;
    }



    public function &Min
    (
        array $AMoments
    )
    {
        return $this -> MinMax
        (
            $AMoments,
            self::MORE
        );
    }



    public function &Max
    (
        array $AMoments
    )
    {
        return $this -> MinMax( $AMoments, self::LESS );
    }



    public function round
    (
        $ACell
    )
    {
        if( !empty( $ACell ))
        {
            $this -> set
            (
                ceil( $this -> get() / $ACell ) * $ACell
            );
        }
        return $this;
    }



    /*
        Floor the moment
    */
    public function floor
    (
        $ACell
    )
    {
        if( !empty( $ACell ))
        {
            $this -> set
            (
                floor( $this -> get() / $ACell ) * $ACell
            );
        }
        return $this;
    }



    /*
        Moment cut by timeseed
    */
    public function &ByTimeseed
    (
        $ATimeseed,
        $AEnd = false
    )
    {
        if( !$AEnd )
        {
            switch( $ATimeseed )
            {
                case 'Hour':
                    $this -> SetLastInterval( self::HOUR );
                break;
                case 'Day':
                    $this  -> SetDayBegin();
                break;
                case 'Month':
                    $this -> SetMonthBegin();
                break;
            }
        }
        else
        {
            switch ($ATimeseed)
            {
                case 'Hour':
                    $this -> SetLastInterval( self::HOUR ) -> Add( self::HOUR );
                break;
                case 'Day':
                    $this  -> SetDayBegin() -> Add( self::DAY );
                break;
                case 'Month':
                    $this -> SetMonthBegin() -> AddMonth();
                break;
            }
        }

        return $this;
    }



    static public function stringToUnit
    (
        string $AString = null
    )
    {
        switch( $AString )
        {
            case self::NAMES[ 'Microsecond' ]   : return self::MICROSECOND;
            case self::NAMES[ 'Millisecond' ]   : return self::MILLISECOND;
            case self::NAMES[ 'Second' ]        : return self::SECOND;
            case self::NAMES[ 'Minute' ]        : return self::MINUTE;
            case self::NAMES[ 'Hour' ]          : return self::HOUR;
            case self::NAMES[ 'Day' ]           : return self::DAY;
            case self::NAMES[ 'Month' ]         : return self::Mounth;
            case self::NAMES[ 'Year' ]          : return self::YEAR;
            default                             : return 0;
        }
    }
}
