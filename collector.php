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



class Collector
{
    private $Root    = null;
    private $Stack   = [];



    function __construct()
    {
        $this -> Root   = self::define();
        array_push( $this -> Stack, $this -> Root );
    }



    /*
        Returen HTTP error code from header array
    */
    static public function create
    (
        array $AHeader = []
    )
    {
        return new Collector();
    }



    /*
        Define new element
    */
    static private function define()
    {
        $Result = (object)[];
        $Result -> Data = [];
        $Result -> Attr = [];
        return $Result;
    }



    /*
        Begin record
    */
    public function open()
    {
        $New = Collector::define();
        array_push( $this -> Stack, $New );
        return $this;
    }



    /*
    */
    public function close()
    {
        $Item = array_pop( $this -> Stack );
        if( !empty( $Item -> Data ) || !empty( $Item -> Attr ))
        {
            array_push( $this -> Stack[ count( $this -> Stack ) - 1 ] -> Data, $Item );
        }
        return $this;
    }



    /*
    */
    public function setAttr
    (
        string $AName,
        string $AValue
    )
    {
        $this -> Stack[ count( $this -> Stack ) - 1 ] -> Attr[ $AName ] = $AValue;
        return $this;
    }



    /*
    */
    public function getRoot()
    {
        return $this -> Root;
    }



    /*
    */
    public function getCurrent()
    {
        return $this -> Current;
    }



    public function loop
    (
        $AOnRecord  = null, /* On Record event */
        $ARecord    = null
    )
    {
        /* Set root as record for empty value */
        if( empty( $ARecord ))
        {
            $ARecord = $this -> getRoot();
        }

        if( !empty( $AOnRecord ) )
        {
            call_user_func( $AOnRecord, [ $ARecord ]);
        }

        foreach( $ARecord -> Data as $Record )
        {
            $this -> loop( $AOnRecord, $Record );
        }

        return $this;
    }
}
