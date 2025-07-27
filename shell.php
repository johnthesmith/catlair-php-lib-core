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


namespace catlair;



require_once 'log.php';
require_once 'result.php';
require_once 'console.php';

/*
    Shell module for work with stdin stdout.
*/
class Shell extends Result
{

    public $CommandMode             = false;
    public $CommandBuffer           = [];
    public $CommandMessageBuffer    = '';
    public $CommandResultLines      = null;
    public $ErrorResultLines        = null;
    public $Log                     = null;
    public $Comment                 = null;

    private $Connection     = '';
    private $PrivateKeyPath = '';


    function __construct( Log $ALog = null )
    {
        $this -> SetOk();
        $this -> Log = $ALog;
    }



    static function create( $ALog = null )
    {
        return new Shell( $ALog );
    }



    /*
        Waiting input confirmation
    */
    public function pause()
    {
        readline( '' );
        return $this;
    }



    /*
        Execute shell command from buffer and clear buffer
    */
    public function cmdBegin()
    {
        $this -> CommandBuffer = [];
        $this -> CommandMode = true;
        return $this;
    }



    /*
        Add command to buffer
    */
    public function cmdAdd
    (
        $ACommand,
        $AQuote = '',
        $ATerminator = ''
    )
    {
        if ( !empty( $ACommand ))
        {
            $this -> CommandBuffer[] =
            $AQuote .
            $ACommand .
            $AQuote .
            $ATerminator;
        }
        return $this;
    }



    /*
        Add file
    */
    public function fileAdd( $aFile )
    {
        if ( !empty( $aFile ))
        {
            $this -> CommandBuffer[] = escapeshellarg( $aFile );
        }
        return $this;
    }



    /*
        Add command argument in to buffer
    */
    public function longAdd
    (
        $aKey,
        $aValue = null
    )
    {
        $this -> CommandBuffer[] =
        '--' .
        $aKey .
        (
            $aValue === null
            ? ''
            : ( '=' . escapeshellarg( $aValue ))
        );
        return $this;
    }



    /*
        Execute command
    */
    public function cmdEnd
    (
        $ADevider   = '',
        $ATest      = false,
        $AWait      = true
    )
    {
        $this -> CommandMode = false;
        if( $this -> isOk() )
        {
            if( $this -> Log != null )
            {
                 $this -> Log
                -> Begin( 'Shell command ' )
                -> Text( $this -> Comment );
            }

            if( $this -> Log != null )
            {
                if( $ATest )
                {
                    $this -> Log -> Warning( 'Test mode' );
                }
                else
                {
                    $this -> Log -> Trace( 'Real mode' );
                }
            }

            $Command = implode( $ADevider, $this -> CommandBuffer );

            /* Build line for remote execution */
            if( !empty( $this -> Connection ))
            {
                $Command = 'ssh -tt ' .
                (
                    empty( $this -> PrivateKeyPath )
                    ? ''
                    : ' -i' . $this -> PrivateKeyPath
                ) .
                $this -> Connection .
                ' "'.
                $Command .
                '"';
            }

            /* end of command mode */
            if( $this -> Log != null ) $this
            -> Log
            -> Trace        () -> Text( gethostname() . '@' . get_current_user() .':' . getcwd() . '# ', Log::COLOR_VALUE )
            -> Trace        () -> Text( $Command, Log::COLOR_TEXT )
            -> LineEnd      ();

            /* Clead commad buffer */
            $this -> CommandMessageBuffer = '';

            /* execute command beffer */
            $ResultText = null;

            $this -> CommandResultLines = [];
            $this -> ErrorResultLines = [];
            $ResultMessage = '';
            $ResultCode = 0;

            if( !$ATest )
            {
                if( empty( $Command ))
                {
                    if( $this -> Log != null ) $this -> Log -> Warning( 'Empty command' );
                }
                else
                {
                    $Pipes =
                    [
                        0 => [ 'pipe', 'r' ],  /* stdin */
                        1 => [ 'pipe', 'w' ],  /* stdout */
                        2 => [ 'pipe', 'w' ]   /* stderr */
                    ];

                    /* Start process */
                    $Proc = proc_open( $Command, $Pipes, $PipesResult, null, null );

                    if( $AWait )
                    {
                        /* Waiting stop process */
                        $this -> CommandResultLines = [];
                        $this -> ErrorResultLines = [];
                        do
                        {
                            $Status = proc_get_status( $Proc );
                            $this -> CommandResultLines = array_merge( $this -> CommandResultLines, explode( PHP_EOL, stream_get_contents( $PipesResult[ 1 ])) );
                            $this -> ErrorResultLines   = array_merge( $this -> ErrorResultLines, explode( PHP_EOL, stream_get_contents( $PipesResult[ 2 ])) );
                            usleep( 10000 );
                        }
                        while ( $Status[ 'running' ] == 1 );

                        $ResultCode = $Status[ 'exitcode' ];

                        /* Close pipes */
                        fclose( $PipesResult[ 0 ]);
                        fclose( $PipesResult[ 1 ]);
                        fclose( $PipesResult[ 2 ]);

                        /* Close process */
                        proc_close( $Proc );
                    }
                }
            }

            /* output params */
            if( $this -> Log != null )
            {
                foreach ( $this -> CommandResultLines as $Line )
                {
                    if( !empty( $Line )) $this -> Log -> Trace( $Line );
                }
                foreach ( $this -> ErrorResultLines as $Line )
                {
                    if( !empty( $Line )) $this -> Log -> Trace( $Line );
                }
            }

            /* set result code for object */
            if( $ResultCode != 0 )
            {
                $this -> setResult( $ResultCode );
            }

            if( $this -> Log != null )
            {
                $this -> Log -> Trace () -> Param( 'Code', $this -> getCode() );
            }

            if( $this -> Log != null ) $this -> Log -> End( 'End command' );
        }

        return $this;
    }



    /*
        Add command to buffer
    */
    public function cmd
    (
        $ACommand,      /* Shell command */
        $ATest = false, /* Command dont execute for true */
        $AWait = true   /* Wait end of command */
    )
    {
        if( $this -> isOk() )
        {
            $this
            -> cmdBegin()
            -> cmdAdd( $ACommand )
            -> cmdEnd( '', $ATest , $AWait );
        }
        return $this;
    }



    public function getErrorResult()
    {
        return implode( PHP_EOL, $this -> ErrorResultLines );
    }



    /*
        Change current directory
    */
    public function changeDirectory( $APath )
    {
        if ( file_exists( $APath ))
        {
            if ( !chdir( $APath ))
            {
                $this
                -> setResult( 'ERROR_CHANGE_DIRECTORY' )
                -> Error()
                -> Param( 'Code', $this->code )
                -> Param( 'Path', $aPath);
            }
        }
        return $this;
    }


    /*
        Add the Key and Value with quotes in format --key="value"
    */
    public function KeyAdd( $AKey, $AValue, $AQuotes = '"' )
    {
        return $this -> CmdAdd( '--' . $AKey . '="' . $AValue . '"' );
    }


    /*
    */
    public function getResult()
    {
        return $this -> CommandResultLines;
    }



    /*
        Return value by key.
        Key will be exclude from line
    */
    public function getResultByKey
    (
        string $AKey,
        string $ADefault =''
    )
    {
        $Result = $ADefault;
        foreach( $this -> CommandResultLines as $Line )
        {
            if( strpos( $Line, $AKey ) !== false )
            {
                $Result = trim( str_replace( $AKey, '', $Line ));
            }
        }
        return $Result;
    }



    public function setComment( $AValue )
    {
        $this -> Comment = $AValue;
        return $this;
    }



    public function setConnection( $AValue )
    {
        $this -> Connection = $AValue;
        return $this;
    }



    public function setPrivateKeyPath( $AValue )
    {
        $this -> PrivateKeyPath =  $AValue;
        return $this;
    }
}
