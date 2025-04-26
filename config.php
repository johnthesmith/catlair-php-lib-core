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
    24.09.2019 - still@itserv.ru
*/
namespace catlair;



require_once( 'utils.php' );
require_once( 'params.php' );



class TConfig extends Params
{
    function  __construct()
    {
        $this -> FFile = '';
    }



    static public function Create()
    {
        return new TConfig();
    }



    /*
        Set file
    */
    public function &SetFile( $AValue )
    {
        $this -> FFile = $AValue;
        return $this;
    }



    /*
        Return file
    */
    public function GetFile()
    {
        return $this -> FFile;
    }



    /*
        Read Config to object
    */
    public function &Read()
    {
        /* Read JSON to file */
        if ( file_exists( $this -> FFile ))
        {
            $Content = file_get_contents($this->FFile);
            $Array = json_decode( clPurgeComments( $Content ), true );

            if( $Array != null )
            {
                $this -> SetParams( $Array );
                $this -> SetOk();
            }
            else
            {
                $this -> SetResult
                (
                    'DecodeConfigError',
                    [],
                    'Code [' . json_last_error() . '] message [' . json_last_error_msg() . '] in file [' . $this -> FFile . ']'
                );
            }
        }
        else $this -> SetResult( 'ConfigNotExists', [], $this -> FFile );
        return $this;
    }



    public function &Flush()
    {
        /* Create filepath */
        $FilePath = dirname( $this->FFile );
        if (!file_exists($FilePath)) mkdir($FilePath, FILE_RIGHT, true);

        /* Write JSON to file */
        $Content = json_encode( $this -> GetParams(), JSON_PRETTY_PRINT );
        if( file_put_contents( $this->FFile, $Content)) $this -> SetOk();
        else $this->SetResult( 'ErrorStoreDatabaseConfig', $AFileName );

        /* Return result */
        return $this;
    }


    /**/
    public function &Delete()
    {
        unlink( $this -> FFile );
        return $this;
    }
}

