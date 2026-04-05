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
    Работа с URL
    Форк pusa.dev
*/


class URL
{
    private $Changed     = false;   /* Url change indicator */

    private $Scheme      = null;
    private $Host        = null;
    private $Port        = null;
    private $Hash        = null;
    private $Params      = null;
    private $Path        = null;



    static function create()
    {
        return new URL();
    }



    function parse( $AURL )
    {
        $URL = parse_url( $AURL );

        $this -> Scheme     = array_key_exists( 'scheme',   $URL ) ? $URL[ 'scheme' ]   : 'http';
        $this -> Host       = array_key_exists( 'host',     $URL ) ? $URL[ 'host' ]     : 'localhost';
        $this -> Port       = array_key_exists( 'port',     $URL ) ? $URL[ 'port' ]     : '';
        $this -> User       = array_key_exists( 'user',     $URL ) ? $URL[ 'user' ]     : '';
        $this -> Password   = array_key_exists( 'pass',     $URL ) ? $URL[ 'pass' ]     : '';
        $this -> Hash       = array_key_exists( 'fragment', $URL ) ? $URL[ 'fragment' ] : '';
        $this -> Query      = array_key_exists( 'query',    $URL ) ? $URL[ 'query' ]    : '';
        $this -> Path       = array_key_exists( 'path',     $URL ) ? $URL[ 'path' ]     : '';

        /* URL parameters */
        $params = [];
        parse_str( $this -> Query, $params );
        $this -> Params = $params;

        /* URL path */
        $this -> Path
        = empty( $this -> Path ) || ($this -> Path == '/' )
        ? []
        : explode( '/', $this -> Path );
        array_shift( $this -> Path );

        $this -> Changed = true;

        return $this;
    }



    /*
        Return the URL for this PusaCongateer
    */
    public function toString()
    {
        /* Scheme*/
        $Result = $this -> Scheme. '://';

        /* Host */
        $Result .= $this -> Host;
        /* Port */
        if( !empty( $this -> Port )) $Result .= ':' . $this -> Port;

        /* Path */
        if( !empty( $this -> Path ) ) $Result .= '/' . implode( '/', $this -> Path );

        /* Params */
        if( !empty( $this -> Params ))
        {
            $Result .= '?' . http_build_query( $this -> Params );
        }

        /* Hash */
        if( !empty( $this -> Hash )) $Result .= '#' . $this -> Hash;

        return $Result;
    }



    public function clearParams()
    {
        $this -> Params = [];
        $this -> Changed = true;
        return $this;
    }



    public function isChanged()
    {
        return $this -> Changed;
    }



    public function noChanged()
    {
        $this -> Changed = false;
        return $this;
    }



    /**************************************************************************
        Getters and setters
    */



    public function setScheme
    (
        string $AValue
    )
    {
        $this -> Scheme = $AValue;
        $this -> Changed = true;
        return $this;
    }



    public function setUser
    (
        string $AValue
    )
    {
        $this -> User = $AValue;
        $this -> Changed = true;
        return $this;
    }



    public function setPassword
    (
        string $AValue
    )
    {
        $this -> Password = $AValue;
        $this -> Changed = true;
        return $this;
    }



    public function setHost
    (
        string $AValue
    )
    {
        $this -> Host = $AValue;
        $this -> Changed = true;
        return $this;
    }



    /*
        Return host from url
    */
    public function getHost()
    :string
    {
        return $this -> Host;
    }



    public function setPort
    (
        string $AValue = null
    )
    {
        $this -> Port = $AValue;
        $this -> Changed = true;
        return $this;
    }



    public function setPath
    (
        array $AValue = []
    )
    {
        $this -> Path = $AValue;
        $this -> Changed = true;
        return $this;
    }



    public function getPath()
    {
        return $this -> Path;
    }



    public function setUri
    (
        string $AValue
    )
    {
        $URL = parse_url( $AValue );

        $this -> Hash       = array_key_exists( 'fragment', $URL ) ? $URL[ 'fragment' ] : '';
        $this -> Query      = array_key_exists( 'query',    $URL ) ? $URL[ 'query' ]    : '';
        $this -> Path       = array_key_exists( 'path',     $URL ) ? $URL[ 'path' ]     : '';

        /* URL parameters */
        $params = [];
        parse_str( $this -> Query, $params );
        $this -> Params = $params;

        /* URL path */
        $this -> Path
        = empty( $this -> Path ) || ($this -> Path == '/' )
        ? []
        : explode( '/', $this -> Path );
        array_shift( $this -> Path );

        $this -> Changed = true;

        return $this;
    }



    public function setParam
    (
        string $aName,
        $aValue = null
    )
    {
        $this -> Params[ $aName ] = $aValue;
        $this -> Changed = true;
        return $this;
    }




    public function getParam
    (
        string $aName,
        $aDefault = null
    )
    {
        return $this -> Params[ $aName ] ?? $aDefault;
    }



    public function getParams()
    {
        return $this -> Params;
    }



    public function setParams
    (
        array $a
    )
    :self
    {
        $this -> Params = $a;
        return $this;
    }


    public function setHash( $AValue = null )
    {
        $this -> Hash = $AValue;
        $this -> Changed = true;
        return $this;
    }



    /*
        Load url from current resuest
    */
    function fromRequest()
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';

        $url = "$scheme://$host$uri";
        return $this->parse( $url );
    }


    /*
        Return true for empty path and query
    */
    public function isEmptyUri()
    {
        return empty( $this -> Path ) && empty( $this -> Query );
    }
}
