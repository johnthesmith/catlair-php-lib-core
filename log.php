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
    Debug system
    Catlair PHP

    Create: 2017
    Update:
    05.03.2019 - Add Dump
    23.08.2019 - Add colord

    still@itserv.ru

    Example:
    $Log = TLog::Create()
    -> Start(true)
    -> Begin()
    -> Info()
    -> Text('Hello world')
    -> Param('Param','Value')
    -> End()
    -> Stop();
*/

namespace catlair;



require_once( 'result.php' );
require_once( 'console.php' );
require_once( 'utils.php' );



/* Define STDOUT const */
if( !defined( 'STDOUT' )) define( 'STDOUT', fopen( 'php://stdout', 'wb' ));



class Log extends Result
{
    const FILE_RIGHT = 0770;

    /* Message type */
    const BEG = '>';    /* Begin job */
    const END = '<';    /* End job */
    const TRS = '~';    /* Information line */
    const INF = 'I';    /* Information line */
    const ERR = 'E';    /* Critical error line */
    const WAR = 'W';    /* Warning line */
    const DEB = '#';    /* Debug line */
    const DLG = '?';    /* Dialog */
    const LIN = '─';    /* Dialog */

    const COLOR_TRACE           = Console::ESC_INK_BLUE;
    const COLOR_INFO            = Console::ESC_INK_CYAN;
    const COLOR_ERROR           = Console::ESC_INK_RED;
    const COLOR_WARNING         = Console::ESC_INK_YELLOW;
    const COLOR_DEBUG           = Console::ESC_INK_AQUA;

    const COLOR_LABEL           = Console::ESC_INK_GREEN;
    const COLOR_TEXT            = Console::ESC_INK_DEFAULT;
    const COLOR_VALUE           = Console::ESC_INK_WHITE;
    const COLOR_TITLE           = Console::ESC_INK_GOLD;
    const COLOR_SYNTAXYS        = Console::ESC_INK_GREY;

    /* Log destination */
    const FILE                  = 'file';
    const CONSOLE               = 'console';

    const CONTINUE              = 'a';
    const CLEAR                 = 'w';

    const LINE                  = '───────────────────────────────────────────────────────────────────────────────';

    /* Private declaration */
    private $MomentLast         = 0;                /* Moment begin for last message */
    private $MomentCurrent      = 0;                /* Moment begin for current message */
    private $InLine             = false;            /* Debugger have begun new line */
    private $CurrentTrace       = null;             /* Current trace information */
    private $CurrentTraceLabel  = '';               /* Trace label for current line */
    private $CurrentString      = '';               /* Full log string message for write to destination with all charaters */
    private $PureString         = '';               /* String message for without escape and control characters */
    private $Stack              = [];               /* Stack for jobs is congateed by Begin and End.*/
    private $TrapEnabled        = false;            /* Trap mekhanism enabled. */
    private $TrapStack          = [];               /* Stack for trap accumulator with erray of string for each element */
    private $TraceResult        = [];
    private $TraceDeltaSec      = 0;
    private $LineType           = '';               /* Type of current line */

    private $LastTrace          = null;
    private $LastDebug          = null;
    private $LastError          = null;
    private $LastWarning        = null;
    private $LastInfo           = null;

    private $OldErrorHandle     = null;         /* Handel for PHP error event */
    private $Handle             = false;

    private $Colored            = true;             /* Color out enable or disable */
    private $Enabled            = true;             /* Enable or disable log */
    private $Job                = true;             /* Enable/disbale job line */
    private $Debug              = true;             /* Enable/disable debug messages */
    private $Trace              = true;             /* Enable/disable trace messeges */
    private $Info               = true;             /* Enable/disable info messeges */
    private $Error              = true;             /* Enable/disable error messages*/
    private $Warning            = true;             /* Enable/disable warning messages */
    private $Header             = true;            /* Show header with moment, trace information, type and depth */

    private $TraceStack         = [];               /* Trace control */

    private $Path               = '.';              /* Path for log file */
    private $File               = 'log';            /* File name for log file */
    private $CurrentType        = null;

    /* Public declarations */
    public $Destination         = self::CONSOLE;    /* Destination for log TLog::CONSOLE, TLog:FILE  */
    public $OpenType            = self::CLEAR;      /* */
    public $TimeWarning         = 500.0;            /* Line highlight when timeout more value*/
    public $CurrentHeader       = true;             /* Current header status */
    public $TraceLine           = true;             /* */
    public $ShowType            = true;             /* */
    public $Tree                = true;             /* */

    /* Statistic */
    private $TraceCount         = 0;
    private $ErrorCount         = 0;
    private $WarningCount       = 0;
    private $InfoCount          = 0;
    private $DebugCount         = 0;

    private $Table              = [];
    private $Column             = 0;
    private $DumpExclude        = [];


    function __construct()
    {
    }



    static function create()
    {
        return new Log();
    }



    public function setEnabled
    (
        bool $AValue = true
    )
    {
        $this -> Enabled = $AValue;
        return $this;
    }



    public function getEnabled()
    {
        return  $this -> Enabled;
    }



    public function getFilePath()
    {
        return $this -> Path . '/' . $this -> File;
    }



    /*
        Return file handle
    */
    private function getHandle()
    {
        return $this -> Handle;
    }



    /*
        Set hadle for current log
    */
    private function setHandle( $AValue )
    {
        $this -> Handle = $AValue;
        return $this;
    }



    /*
        Write current $AString:string to destination
    */
    public function write
    (
        string $AString
    )
    {
        if( $this -> Enabled )
        {
            if( empty( $this -> getHandle()) && $this -> Destination == self::FILE )
            {
                $File = $this -> getFilePath();
                if ( $File != '' && clCheckPath( dirname( $File )) )
                {
                    @chmod( dirname( $File ), 0777 );
                    $this -> setHandle( @fopen( $File, $this -> OpenType ));
                    @chmod( $File, 0666 );
                    if( empty( $this -> getHandle()))
                    {
                        $this -> setResult
                        (
                            'LogFileOpenError',
                            [
                                'FileName' => $File,
                                'Error' => error_get_last()[ 'message' ]
                            ]
                        );
                    }
                }
                else
                {
                    $this -> setHandle( false );
                }
            }

            if( !empty( $this -> getHandle()))
            {
                /*Write to file*/
                $r = fwrite( $this -> getHandle(), $AString );
                if( $r === false )
                {
                    $this -> setResult
                    (
                        'LogFileWriteError',
                        [
                            'Error' => error_get_last()[ 'message' ]
                        ]
                    );
                }
            }
            else
            {
                /*Write to console*/
                fwrite( STDOUT, $AString );
            }
        }
        return $this;
    }



    /*
        Text $AString:string is outed to current line
    */
    private function store( $AString, $APure )
    {
        $this -> CurrentString .= $AString;
        if ($APure) $this -> PureString .= $AString;
        return $this;
    }



    /*
        Out trace information
    */
    public function traceTotal()
    {
        if( $this -> Enabled )
        {
            $this
            -> tracePush()
            -> setTrace( true )
            -> begin( 'Trace total' )
            -> setTable
            ([
                [ 'Color' => Log::COLOR_LABEL, 'Length' => 40, 'Pad' => ' ', 'Align' => STR_PAD_RIGHT ],
                [ 'Color' => Log::COLOR_VALUE, 'Length' => 20, 'Pad' => ' ', 'Align' => STR_PAD_LEFT ],
                [ 'Color' => Log::COLOR_VALUE, 'Length' => 10, 'Pad' => ' ', 'Align' => STR_PAD_LEFT ]
            ]);
            $this -> trace()
            -> cell( 'Call',    self::COLOR_TITLE )
            -> cell( 'Time ms', self::COLOR_TITLE )
            -> cell( 'Count',   self::COLOR_TITLE );

            /* Trace information sorting */
            uasort
            (
                $this -> TraceResult,
                function($a, $b)
                {
                    if( $a[ 'Delta' ] > $b[ 'Delta' ]) return -1;
                    elseif( $a[ 'Delta' ] < $b[ 'Delta' ]) return 1;
                    else return 0;
                }
            );

            foreach ($this -> TraceResult as $Key => $Value)
            {
                $this
                -> trace()
                -> cell( $Key )
                -> cell( number_format( $Value['Delta'] * 1000, 2, '.', ' ' ))
                -> cell( $Value['Count']);
            }

            $this
            -> tracePop()
            -> end( '' );
        }
        return $this;
    }



    /*
        Begin of new line with type $AType form self::TYPE
    */
    public function lineBegin( $AType )
    {
        if( $this -> Enabled )
        {
            /* If line is begined then close the line */
            if( $this -> InLine ) $this -> lineEnd();

            $this -> Column         = 0;
            $this -> CurrentType    = $AType;
            $this -> CurrentString  = '';
            $this -> PureString     = '';

            /* Get trace information for line */
            $this -> CurrentTrace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT );

            /*Begin new line*/
            $this -> InLine = true;
            $this -> LineType = $AType;
            $this -> MomentCurrent = microtime(true);

            /* Push begin in to stack */
            if ( $AType == self::BEG )
            {
                /* Store trace information */
                array_push
                (
                    $this -> Stack,
                    [
                        'Moment'        => $this -> MomentCurrent,
                        'TraceLabel'    => $this -> CurrentTraceLabel
                    ]
                );
            }

            /* Check log enabled and other settings */
            if
            (
                $AType == self::LIN && $this -> Trace ||
                $AType == self::ERR && $this -> Error ||
                $AType == self::DEB && $this -> Debug ||
                $AType == self::WAR && $this -> Warning ||
                $AType == self::INF && $this -> Info ||
                $AType == self::TRS && $this -> Trace ||
                $AType == self::BEG && $this -> Job ||
                $AType == self::END && $this -> Job
            )
            {
                /* Calculate delta */
                $Delta = $this -> MomentLast == 0 ? 0 : ( $this -> MomentCurrent - $this -> MomentLast ) * 1000;

                if( $this -> CurrentHeader )
                {
                    $Tab = 10;

                    /* Trace information */
                    if( $this -> TraceLine )
                    {
                        $Tab += 20;

                        $this -> Color( Console::ESC_INK_SILVER ) -> Text(date('Y-m-d H:i:s'));

                        $this -> Text
                        (
                            strPad
                            (
                                (string) ( number_format( $Delta, 2, '.', ' ' )),
                                16,
                                '.',
                                STR_PAD_LEFT
                            ),
                            $Delta > $this -> TimeWarning
                            ? self::COLOR_WARNING
                            : self::COLOR_SYNTAXYS
                        )
                        /* Tabulate */
                        -> Color(self::COLOR_SYNTAXYS)
                        -> Tab( $Tab )
                        -> Color(Console::ESC_INK_DEFAULT);
                    }
                }

                /* Type information */
                if( $this -> ShowType )
                {
                    /* Color */
                    switch ( $this -> LineType )
                    {
                        default:
                        case self::DEB: $Color = self::COLOR_DEBUG; break;
                        case self::TRS: $Color = self::COLOR_TRACE; break;
                        case self::INF: $Color = self::COLOR_INFO; break;
                        case self::WAR: $Color = self::COLOR_WARNING; break;
                        case self::ERR: $Color = self::COLOR_ERROR; break;
                        case self::LIN: $Color = Console::ESC_INK_SILVER; break;
                        case self::BEG: $Color = Console::ESC_INK_GOLD; break;
                        case self::END: $Color = Console::ESC_INK_GOLD; break;
                        case self::DLG: $Color = Console::ESC_INK_BLUE; break;
                    }
                    $this -> Text( ' ' . $AType . ' ', $Color );
                }

                /* Timing display */
                if( $this -> Tree && $this -> Job )
                {
                    /* Depth shift */
                    $Depth = count( $this -> Stack );
                    if( $this -> LineType == self::BEG || $this -> LineType == self::END ) $Depth -- ;
                    if( $Depth > 0 )
                    {
                        $this -> Text
                        (
                            str_repeat( '│ ', $Depth - 1 ) . ( $this -> LineType == self::BEG ? '├─' : '│ ' ),
                            self::COLOR_SYNTAXYS
                        );
                    }
                }

                /* Output trace label */
                if ( $AType == self::BEG )
                {
                    $this -> Text( $this -> CurrentTraceLabel,  self::COLOR_TITLE );
                }

            }
        }

        return $this;
    }



    /*
        Close current line
    */
    public function lineEnd()
    {
        if( $this -> Enabled )
        {
            if ( $this -> InLine )
            {
                $this -> MomentLast = $this -> MomentCurrent;
                /* Write End of line */
                $this -> EOL();
                $this -> InLine = false;
            }

            switch ( $this -> CurrentType )
            {
                case self::DEB: $this -> LastDebug      = $this -> CurrentString; break;
                case self::INF: $this -> LastInfo       = $this -> CurrentString; break;
                case self::WAR: $this -> LastWarning    = $this -> CurrentString; break;
                case self::ERR: $this -> LastError      = $this -> CurrentString; break;
                case self::TRS: $this -> LastTrace      = $this -> CurrentString; break;
            }

            /* Write to file */
            if
            (
                $this -> CurrentType == self::LIN && $this -> Trace ||
                $this -> CurrentType == self::ERR && $this -> Error ||
                $this -> CurrentType == self::DEB && $this -> Debug ||
                $this -> CurrentType == self::WAR && $this -> Warning ||
                $this -> CurrentType == self::INF && $this -> Info ||
                $this -> CurrentType == self::TRS && $this -> Trace ||
                $this -> CurrentType == self::BEG && $this -> Job ||
                $this -> CurrentType == self::END && $this -> Job
            )
            {
                $this -> Flush();
            }
        }
        return $this;
    }



    public function flush()
    {
        $TrapDepth = count( $this -> TrapStack );
        if( $TrapDepth > 0 && $this -> TrapEnabled)
        {
            array_push
            (
                $this -> TrapStack[ $TrapDepth - 1 ],
                $this -> CurrentString
            );
        }
        else
        {
            $this -> Write( $this -> CurrentString );
        }
        $this -> CurrentString = '';
        return $this;
    }



    /*
        Start debug with $AEnabled:boolean
    */
    public function start()
    {
        /* reset job stack */
        $this -> Stack          = [];
        /* reset trace array */
        $this -> TraceArray     = [];
        /* reset statistic */
        $this -> ErrorCount     = 0;
        $this -> WarningCount   = 0;
        $this -> InfoCount      = 0;
        $this -> DebugCount     = 0;
        /* write last moment */
        $this -> CurrentLast   = microtime( true );
        return $this;
    }



    /*
        Stop debug
    */
    public function stop()
    {
        $this -> LineEnd();

        /* Close all files if it was opened. */
        if( !empty( $this -> getHandle() ) )
        {
            fclose( $this -> getHandle() );
        }

        return $this;
    }



    public function getLastWarning()
    {
        return $this -> LastWarning;
    }



    public function getLastError()
    {
        return $this -> LastError;
    }



    /*
        Dump to log the last error and warnig messeges
    */
    public function lastMessages( $ALog = null )
    {
        if( empty( $ALog )) $ALog = $this;
        if ( $this->Enabled )
        {
            /* Last messages */
            if( $ALog -> LastWarning != null || $ALog -> LastError != null )
            {
                $this
                -> Line( 'Last warning and error' ) -> LineEnd()
                -> Text( $ALog -> LastWarning ) -> LineEnd()
                -> Text( $ALog -> LastError ) -> LineEnd();
            }
        }
        return $this;
    }



    public function getStatisticString()
    {
        return
        self::COLOR_ERROR .       $this -> ErrorCount . ' ' .
        self::COLOR_WARNING .     $this -> WarningCount . ' ' .
        self::COLOR_DEBUG .       $this -> DebugCount . ' ' .
        self::COLOR_INFO .        $this -> InfoCount . ' ' .
        self::COLOR_TRACE .       $this -> TraceCount . ' ' .
        Console::ESC_INK_DEFAULT;
    }



    public function statisticOut( $ALog = null )
    {
        if( empty( $ALog )) $ALog = $this;
        if( $this -> Enabled ) $this -> ParamLine( 'Events count', $ALog -> GetStatisticString() );
        return $this;
    }



    public function cell
    (
        $AString,
        $AColor     = null,
        $ALength    = null,
        $APad       = null,
        $AAlign     = null
    )
    {
        if( array_key_exists( $this -> Column, $this -> Table ))
        {
            $Column     = $this -> Table[ $this -> Column ];
            $AColor     = !empty( $AColor )     ? $AColor   : ( array_key_exists( 'Color', $Column )    ? $Column[ 'Color' ]    : null );
            $ALength    = !empty( $ALength )    ? $ALength  : ( array_key_exists( 'Length', $Column )   ? $Column[ 'Length' ]   : null );
            $APad       = !empty( $APad )       ? $APad     : ( array_key_exists( 'Pad', $Column )      ? $Column[ 'Pad' ]      : null );
            $AAlign     = !empty( $AAlign )     ? $AAlign   : ( array_key_exists( 'Align', $Column )    ? $Column[ 'Align' ]    : null );
        }

        $this
        -> text( $this -> Column == 0 ? '' : '│', self::COLOR_SYNTAXYS )
        -> text( $AString, $AColor, $ALength, $APad, $AAlign );

        $this -> Column ++;
        return $this;
    }



    /*
        Text $AString:string is outed to log
    */
    public function text
    (
        $AString    = null,
        $AColor     = null,
        $AMaxLength = null,
        $APad       = ' ',
        $AAlign     = STR_PAD_RIGHT
    )
    {
        if ( $this -> Enabled && ( !empty( $AString ) || !empty( $AMaxLength )) )
        {
            if( $AColor != null )
            {
                $this -> Color( $AColor );
            }
            if( $AMaxLength != null )
            {
                $AString = strPad( $AString, $AMaxLength, $APad, $AAlign );
            }
            $this -> Store( (string) $AString, true );
            if ( $AColor != null ) $this -> Color( Console::ESC_INK_DEFAULT );
        }
        return $this;
    }



    /*
        Color $AColor:Console::ESC_* is set for next output
        $AColor - escape sequence from constant Console::ESC_*
    */
    public function color( $AColor )
    {
        if ( $this -> Enabled && $this -> Colored ) $this -> Store( $AColor, false );
        return $this;
    }



    /*
        Set tabulate to $APosition:integer for current line
    */
    public function tab( $APosition )
    {
        if ( $this -> Enabled )
        {
            $l = strlen( $this->PureString );
            if( $l<$APosition ) $this -> Text(str_repeat( ' ', $APosition-$l ));
        }
        return $this;
    }



    /*
        write $AValue to current line
    */
    public function value
    (
        $AValue,
        $AColor = self::COLOR_VALUE,
        $AType = true
    )
    {
        if ( $this -> Enabled )
        {
            $Type = gettype($AValue);
            switch ($Type)
            {
                case 'string':
                    $AValue = preg_replace('/\n/', ' ', $AValue);
                    /* $AValue = preg_replace('/\s\s+/', ' ', $AValue); */
                    $l = strlen( $AValue );
                    $Value =  $l > 1024 ? substr( $AValue, 0, 1024 ) . '...' . $l : $AValue;
                    $Type = 'string';
                break;
                case 'object':
                case 'array':
                    $Value = json_encode
                    (
                        $AValue,
                        JSON_UNESCAPED_UNICODE|
                        JSON_UNESCAPED_SLASHES
                    );
                break;
                case 'boolean':
                    if ( $AValue ) $Value = 'true';
                    else $Value = 'false';
                    $Type = 'boolean';
                break;
                case 'integer':
                    $Value = sprintf( '%0d', $AValue );
                    $Type='integer';
                break;
                case 'double':
                    $Value = sprintf( '%0.f', $AValue );
                    $Type='double';
                break;
                case 'RESOURCE':
                    $Value = NULL;
                break;
                case 'NULL':
                    $Value = NULL;
                break;
                default:
                    $Value = 'UNKNOWN';
                break;
            }
            $this -> Text( $Value, $AColor );
            if( $AType ) $this -> Text( ' ' . $Type, self::COLOR_SYNTAXYS );
        }
        return $this;
    }



    /*
        Out parameter with $AName:string and $AValue:any
        to current line for result [Name = type:Value]
    */
    public function param( $AName, $AValue )
    {
        if ( $this -> Enabled )
        {
            $this
            -> Color( self::COLOR_SYNTAXYS )
            -> Text('[')
            -> Color( self::COLOR_LABEL )
            -> Text(  ' ' .$AName )
            -> Color( self::COLOR_SYNTAXYS )
            -> Text(' = ')
            -> Value( $AValue )
            -> Color( self::COLOR_SYNTAXYS )
            -> Text(' ]')
            -> Color( Console::ESC_INK_DEFAULT );
        }
        return $this;
    }



    /*
        List of parameters
    */
    public function params
    (
        $AValue,        /* Any type value for print */
        $AKey = null    /* Key name for simple values */
    )
    {
        if ( $this -> Enabled )
        {
            $Type = gettype( $AValue );
            switch( $Type )
            {
                case 'NULL':
                case 'RESOURCE':
                case 'double':
                case 'integer':
                case 'boolean':
                case 'string': $this -> param( $AKey, $AValue ); break;
                case 'array':
                case 'object':
                    foreach( $AValue as $Key => $Value)
                    {
                        if( $Value !== $AValue ) $this -> params( $Value, $Key );
                    }
                break;
            }
        }
        return $this;
    }





    /*
        Out parameter with $AName:string and $AValue:any
        to current line for result [Name = type:Value]
    */
    public function key( $AName, $AValue, $AMaxLength = 30 )
    {
        if( $this -> Enabled )
        {
            $this
            -> Text( $AName, self::COLOR_LABEL, $AMaxLength, ' ', STR_PAD_RIGHT )
            -> Value( $AValue );
        }
        return $this;
    }



    /*
        Out parameter with $AName:string and $AValue:any
        to current line for result [Name = type:Value]
    */
    public function paramLine( $AName, $AValue, $AMaxLength = 30, $AColor = self::COLOR_VALUE )
    {
        if ( $this -> Enabled )
        {
            $this
            -> Trace( '' )
            -> Text( $AName, self::COLOR_LABEL, $AMaxLength, ' ', STR_PAD_LEFT )
            -> Text( ': ', self::COLOR_SYNTAXYS )
            -> Value( $AValue, $AColor, false );
        }
        return $this;
    }



    /*
        Set trace label from $ALabel
    */
    public function label($ALabel)
    {
        $this -> CurrentTraceLabel=$ALabel;
        return $this;
    }



    /*
        Job begin for new line
    */
    public function begin( $AText = null )
    {
        if( $this -> Enabled )
        {
            $this -> CurrentTraceLabel = $AText;
            $this -> LineBegin( self::BEG );
        }
        return $this;
    }



    /*
        Job end
    */
    public function end( $AText = null )
    {
        if ( $this -> Enabled )
        {
            if( $this -> Job )
            {
                $this -> LineBegin( self::END ) -> Text( '└─', self::COLOR_SYNTAXYS );
            }

            if ( count( $this -> Stack ) > 0) $StackRecord = array_pop( $this -> Stack );
            else $StackRecord = null;

            if ( $StackRecord != null )
            {
                /* Calculate delta from job begin */
                $this -> TraceDeltaSec = $this -> MomentCurrent - $StackRecord[ 'Moment' ];
                $TraceLabel = $StackRecord[ 'TraceLabel' ];

                $this -> Text
                (
                    (string)(number_format( $this -> TraceDeltaSec * 1000, 2,'.',' ')).'mls ',
                    Console::ESC_INK_GREY
                );

                if ( array_key_exists( $TraceLabel, $this->TraceResult ))
                {
                    $this -> TraceResult[ $TraceLabel ][ 'Delta' ] += $this -> TraceDeltaSec;
                    $this -> TraceResult[ $TraceLabel ][ 'Count' ] ++;
                }
                else
                {
                    $this -> TraceResult[ $TraceLabel ][ 'Delta' ] = $this -> TraceDeltaSec;
                    $this -> TraceResult[ $TraceLabel ][ 'Count' ] = 1;
                }
            }
            else
            {
                $this -> Text(' Tracert heracly error ', Console::ESC_INK_RED );
                $this -> traceDump();
            }

            if ( $AText != null ) $this -> Text( $AText, Console::ESC_INK_SILVER );
        }
        return $this;
    }



    /*
        Wait user input
    */
    public function dialog
    (
        $ADefault = ''
    )
    {
        $Input = '';

        if ( $this -> Enabled )
        {
            if ($this->InLine)
            {
                $this->MomentLast = $this->MomentCurrent;
                $this->Text('>');
                $this->InLine = false;
            }
            /* Write to file*/
            $this -> Write( $this -> CurrentString );
            $Input = readline();
        }

        if ( $Input == '' ) $Input=$ADefault;

        return $Input;
    }



    /*
        Waiting input confirmation
    */
    public function confirm($AMessage, $AYes)
    {
        if ( $this -> Enabled )
        {
            $this
            -> color( Console::ESC_INK_SKY )
            -> text($AMessage)
            -> text(' (')
            -> color( TLog::ESC_INK_AQUA )
            -> text( $AYes )
            -> color(Console::ESC_INK_SKY)
            -> text(')')
            -> color(Console::ESC_INK_DEFAULT)
            -> end();
            $Input = readline();
        }
        return $this;
    }



    /*
        New trace line
    */
    public function trace
    (
        $AText = null
    )
    {
        if ( $this -> Enabled )
        {
            $this -> lineBegin( self::TRS );
            $this -> TraceCount++;
            if ( $AText !=null ) $this -> text( $AText, self::COLOR_TEXT );
        }
        return $this;
    }


    /*
        New debug line
    */
    public function debug( $AText = null )
    {
        if( $this -> Enabled )
        {
            $this -> lineBegin( self::DEB ) -> text( $AText, self::COLOR_DEBUG );
            $this -> DebugCount++;
        }
        return $this;
    }



    /*
        New information line
    */
    public function info( $AText = null )
    {
        if ( $this -> Enabled )
        {
            $this -> lineBegin( self::INF );
            $this -> InfoCount++;
            if( $AText !=null ) $this -> text( $AText, self::COLOR_TEXT );
        }
        return $this;
    }



    /*
        New warning line
    */
    public function warning( $AText = null )
    {
        if ( $this -> Enabled )
        {
            $this -> trapDump();
            $this -> lineBegin(self::WAR);
            $this -> WarningCount++;
            if ( $AText !=null ) $this -> text( $AText, self::COLOR_WARNING );
        }
        return $this;
    }



    /*
        New error line
    */
    public function error( $AText = null )
    {
        if ( $this -> Enabled )
        {
            $this -> trapDump();
            $this -> lineBegin( self::ERR );
            $this -> ErrorCount++;
            if ( $AText != null ) $this -> text( $AText, self::COLOR_ERROR );
        }
        return $this;
    }



    /*
        New dump line
    */
    public function line
    (
        $ALabel = '',
        $AColor = null
    )
    {
        if ( $this -> Enabled )
        {
            $ALabel = empty( $ALabel ) ? '' : ' ' . $ALabel . ' ';

            $this
            -> lineBegin( self::LIN )
            -> text( '──' . $ALabel . str_repeat( '─', 77 - mb_strlen( $ALabel )), $AColor )
            -> lineEnd();
        }
        return $this;
    }



    public function separator( $ACaption, $AColor = null )
    {
        return $this
        -> headerHide()
        -> line( $ACaption, $AColor )
        -> headerRestore();
    }



    /*
        Print text
    */
    public function prn
    (
        $aValue,
        $aLabel = ''
    )
    {
        if ( $this -> Enabled && $this -> Trace )
        {
            $lines = explode( PHP_EOL, $aValue );
            $l = 77 - mb_strlen( $aLabel );

            /* Store Header for resotre in the ond of funciton */
            $this
            -> eol          ()
            -> color        ( Console::ESC_INK_MAGENTA )
            -> text         ( '──' . $aLabel . str_repeat( '─', $l ) )
            -> eol          ()
            -> text         ( $aValue )
            -> eol          ()
            -> color        ( Console::ESC_INK_MAGENTA )
            -> text         ( self::LINE )
            -> color        ( Console::ESC_INK_DEFAULT )
            -> lineEnd      ();
        }
        return $this;
    }



    /* New dump line */
    public function dump
    (
        $AValue,                /* Value, array or object for dump */
        $AText      = 'Dump',   /* Dump prefix text for information */
        $ADepth     = null      /* Depth for dump */
    )
    {
        if( $this -> Enabled && empty( $ADepth ) )
        {
            $Type = gettype( $AValue );
            switch( $Type )
            {
                case 'NULL':
                case 'RESOURCE':
                case 'double':
                case 'integer':
                case 'boolean':
                case 'string':
                    $this -> trace() -> param( $AText, $AValue );
                break;
                case 'array':
                case 'object':
                    $this -> begin( $AText );
                    foreach( $AValue as $Key => $Value )
                    {
                        if( $Value !== $AValue )
                        {
                            $this -> dump
                            (
                                ( empty( $this -> DumpExclude ) || ! in_array( $Key, $this -> DumpExclude ))
                                ? $Value : '******',
                                $Key,
                                $ADepth === null ? null : $ADepth - 1
                            );
                        }
                    }
                    $this -> end();
                break;
            }
        }
        return $this;
    }



    /* New error line */
    public function eol()
    {
        $this -> text( PHP_EOL );
        return $this;
    }



    public function setDestination( $ADestination )
    {
        $this -> Destination = $ADestination;
        return $this;
    }



    public function getDestination()
    {
        return $this -> Destination;
    }



    /*
        Files and pathes
    */

    /* Set log file from $AFile */
    public function setLogFile( $AFile )
    {
        if( empty( $AFile ))
        {
            $AFile='index';
        }
        else
        {
            if( strlen( $AFile ) > 250 )
            {
                $AFile = md5( $AFile );
            }
            else
            {
                $AFile = clFileControl( $AFile );
            }
        }

        /* Write propery */
        $this -> File = $AFile . '.log';
        return $this;
    }



    /*
        Get file
    */
    public function getLogFile()
    {
        return $this -> File;
    }



    /*
        Set file
    */
    public function setLogPath
    (
        $APath
    )
    {
        $this -> Path = $APath;
        return $this;
    }



    /*
        Get file
    */
    public function getLogPath()
    {
        return $this -> Path;
    }



    /**************************************************************************
        Trap. Log in trap section does not output, but storeging in to a stack
        of the trap. For warning and error cases, current trap buffer will be
        dump to the log.
    */



    /*
        Begin trap section.
    */
    public function trapBegin()
    {
        if( $this -> TrapEnabled )
        {
            $this -> lineEnd();
            array_push( $this -> TrapStack, [] );
        }
        return $this;
    }



    /*
        Trap dump and end
    */
    public function trapDump()
    {
        foreach( $this -> TrapStack as $Current )
        {
            if( !empty( $Current ))
            {
                $this
                -> write( implode( $Current ))
                -> trapEnd();
            }
        }
        $this -> TrapStack = [];
        return $this;
    }



    /*
        End of trap section
    */
    public function trapEnd()
    {
        if( $this -> TrapEnabled )
        {
            $this -> lineEnd();
            array_pop( $this -> TrapStack );
        }
        return $this;
    }



    public function setTrapEnabled
    (
        bool $AValue = true
    )
    {
        $this -> TrapEnabled = $AValue;
        return $this;
    }



    public function getTrapEnabled()
    {
        return  $this -> TrapEnabled;
    }



    /**************************************************************************
        Utils
    */

    /*
        Get show depth
    */
    public function headerShow()
    {
        $this -> Header = $this -> CurrentHeader;
        $this -> CurrentHeader = true;
        return $this;
    }



    /*
        Set file
    */
    public function headerHide()
    {
        $this -> Header = $this -> CurrentHeader;
        $this -> CurrentHeader = false;
        return $this;
    }



    /*
        Restore header
    */
    public function headerRestore()
    {
        $this -> CurrentHeader = $this -> Header;
        return $this;
    }



    /*
        Setter for the header
    */
    public function setHeader( $AValue )
    {
        $this -> Header = $AValue;
        $this -> CurrentHeader = $AValue;
        return $this;
    }



    /*
        Getter for the current header
    */
    public function getGurrentHeader()
    {
        return $this -> CurrentHeader;
    }



    /*
        Getter for the header
    */
    public function getHeader()
    {
        return $this -> Header;
    }



    /* Show trace information for each line */
    public function traceShow()
    {
        $this -> TraceLine = true;
        return $this;
    }



    /*
        Hide trace information for each line
    */
    public function traceHide()
    {
        $this -> TraceLine = false;
        return $this;
    }



    /*
        Enable output the log
    */
    public function enable()
    {
        $this -> Enabled = true;
        return $this;
    }



    /*
        Disable output the log
    */
    public function disable()
    {
        $this -> Disabled = false;
        return $this;
    }



    /*
        Catch all PHP errors and put them in to log
    */
    public function catchErrors()
    {
        if ( $this -> OldErrorHandle == null )
        {
            $this -> OldErrorHandle = set_error_handler
            (
                function ( $errno, $errstr, $errfile, $errline )
                {
                    $this
                    -> error( $errstr )
                    -> traceDump();
                }
            );
        }

        return $this;
    }



    public function traceDump()
    {
        $Debug = debug_backtrace();

        $this -> begin( 'Trace' );
        foreach( $Debug as $Record )
        {
            $this -> trace()
            -> text( $Record[ 'function' ])
            -> text(' ')
            -> text( clValueFromObject( $Record, 'line' ), self::COLOR_LABEL )
            -> text(' ')
            -> text( dirname( clValueFromObject( $Record, 'file' ) ) . '/' )
            -> text( basename( clValueFromObject( $Record, 'file' ) ), self::COLOR_LABEL );
        }
        $this -> end();
        return $this;
    }



    public function reset()
    {
        $Handle = $this -> getHandle();
        if ( $Handle != -1 )
        {
            fclose( $Handle );
            $this -> setHandle( false );
        }
        return $this;
    }



    public function setTable( $AValue )
    {
        $this -> Table = $AValue;
        return $this;
    }



    public function setExecute( $AValue )
    {
        $this -> Execute = $AValue;
        return $this;
    }



    public function getExecute()
    {
        return $this -> Execute;
    }




    public function filePathToStd()
    {
        print_r( 'Log file:' . $this -> getFilePath() . PHP_EOL );
        return $this;
    }



    /*
        Сохранение результата в файл
    */
    public function saveResultToFile
    (
        $APath,     /* путь к файлу */
        $AData      /* данные для сохранения */
    )
    {
        file_put_contents
        (
            $APath,
            json_encode($AData, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)
        );
        return $this;
    }



    /*
        Установка исключений для файла
    */
    public function setDumpExclude
    (
        array $AValue
    )
    {
        $this -> DumpExclude = $AValue;
        return $this;
    }



    /*
        Установка вывода отладочных сообщений
    */
    public function setDebug
    (
        bool $AValue
    )
    {
        $this -> Debug = $AValue;
        return $this;
    }



    /*
        Установка вывода информационных сообщений
    */
    public function setInfo
    (
        bool $AValue
    )
    {
        $this -> Info = $AValue;
        return $this;
    }



    /*
        Установка вывода предупреждающих сообщений
    */
    public function setWarning
    (
        bool $AValue
    )
    {
        $this -> Warning = $AValue;
        return $this;
    }




    /*
        Установка вывода сообщений об ошибках
    */
    public function setError
    (
        bool $AValue
    )
    {
        $this -> Error = $AValue;
        return $this;
    }




    /*
        Установка вывода трассировочных сообщений
    */
    public function setJob
    (
        bool $AValue
    )
    {
        $this -> Job = $AValue;
        return $this;
    }



    /*
        Установка вывода трассировочных сообщений
    */
    public function setColored
    (
        bool $AValue
    )
    {
        $this -> Colored = $AValue;
        return $this;
    }



    /*
        Установка вывода дерева
    */
    public function setTree
    (
        bool $AValue
    )
    {
        $this -> Tree = $AValue;
        return $this;
    }



    /*
        Установка вывода дерева
    */
    public function setTimeWarning
    (
        float $AValue
    )
    {
        $this -> TimeWarning = $AValue;
        return $this;
    }



    /*
        Установка вывода трассировочных сообщений
    */
    public function setTrace
    (
        bool $AValue
    )
    {
        $this -> Trace = $AValue;
        return $this;
    }



    /*
        Store trace state to the stack
    */
    public function tracePush()
    {
        array_push( $this -> TraceStack, $this -> Trace );
        return $this;
    }



    /*
        Restore track state from the stack
    */
    public function tracePop()
    {
        $this -> setTrace( array_pop( $this -> TraceStack ));
        return $this;
    }



    /*
        Return the trace result
    */
    public function getTraceResult()
    {
        return $this -> TraceResult;
    }


    public function getTraceDeltaSec()
    {
        return $this -> TraceDeltaSec;
    }

}
