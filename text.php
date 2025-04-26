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



/*
    Basic module for the text line compile.
    2019-11-28 still@itserv.ru
*/



/* Includ libraryes */
require_once 'params.php';



/* Class Text */
class Text extends Params
{
    /* Public declarations */
    private $Content    = [];



    /* Constructor */
    function __construct()
    {
        $this -> SetOk();
    }



    static function Create()
    {
        $Result = new self();
        return $Result;
    }



    function getContent( $Separator = '' )
    {
        $Result = implode( $Separator, $this -> Content );
        foreach( $this -> GetParams() as $Key => $Value )
        {
            $Result = str_replace( '%' . $Key . '%', $Value, $Result );
        }
        return $Result;
    }



    function setContent( $AValue = '' )
    {
        $this -> Content = [];
        array_push( $this -> Content, $AValue );
        return $this;
    }



    /*
        write $aText:string in to content buffer
    */
    function add( $AValue )
    {
        switch ( gettype( $AValue ))
        {
            case 'array':
            case 'object':
                foreach( $AValue as $Key ) $this -> Add( $Key ) -> EOL();
            break;
            default: array_push( $this -> Content, $AValue );
        }

        return $this;
    }



    /*
        write end of line
    */
    function &EOL()
    {
        $this -> Add( PHP_EOL );
        return $this;
    }



    /*
        write $aValue to current line
    */
    public function value
    (
        $AValue
    )
    {
        $Type = gettype( $AValue );
        switch ($Type)
        {
            case 'string':
                $AValue = preg_replace('/\n/', ' ', $AValue);
                $AValue = preg_replace('/\s\s+/', ' ', $AValue);
                $l=strlen($AValue);
                if( $l > 512 ) $value = substr( $AValue, 0, 512 ).'...'.$l;
                else $Value = $AValue;
                $Type='s';
            break;
            case 'array':
                $Value = (string)count($AValue);
            break;
            case 'boolean':
                $Value = (string)$AValue;
                $Type='b';
            break;
            case 'integer':
                $Value = (integer)$AValue;
                $Type='i';
            break;
            case 'double':
                $Value = $AValue;
                $Type='d';
            break;
            case 'object':
                $Value = NULL;
            break;
            case 'RESOURCE':
                $Value = NULL;
            break;
            case 'NULL':
                $Value = NULL;
            break;
        }

        $this
        -> Add( $Type )
        -> Add( ':' )
        -> Add( $Value );

        return $this;
    }



    /*
        Out parameter with $AName:string and $AValue:any to current line for result [Name = type:Value]
    */
    public function &Param( $AName, $AValue)
    {
        $this
        -> Add( '[' )
        -> Add( $AName )
        -> Add( '=' )
        -> Value( $AValue )
        -> Add( ']' );
        return $this;
    }



    /*
        dump object or array
    */
    public function &Dump( $AValue )
    {
        switch( gettype( $AValue ))
        {
            case 'NULL':
            case 'RESOURCE':
            case 'double':
            case 'integer':
            case 'boolean':
            case 'string': $this -> Value( $AValue ); break;
            case 'array':
            case 'object':
                foreach( $AValue as $Key => $Value )
                {
                    if( $Value !== $AValue )
                    {
                        $this -> Text( $Key . ':' ) -> Dump( $Value );
                    }
                }
            break;
        }
        return $this;
    }



    public function loop
    (
        array $AArray,
        $ACallback
    )
    {
        foreach( $AArray as &$item )
        {
            $ACallback( $this, $item );
        }
        return $this;
    }
}
