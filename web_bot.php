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

    2019-02-21 still@itserv.ru
*/


namespace catlair;



require_once 'params.php';
require_once 'url.php';



class WebBot extends Params
{
    const HTTP      = 'http';
    const HTTPS     = 'https';

    private $Url                = null;
    private $Post               = null;
    private $Content            = null;
    private $Answer             = null; /* Object JSON, DOM, or other*/
    private $Handle             = null; /* Current request handle */
    private $DumpContent        = false;
    private $RequestTimeoutMls  = 1000; /* Length of request at mls befor drop down it */
    private $contentType        = null;

    /*
        Constructor
    */
    function __construct( $ALog )
    {
        $this -> Url    = Url::create();
        $this -> Log    = $ALog;
        $this -> Get    = new Params();
        $this -> Post   = new Params();
        $this -> SetOk();
    }



    /*
        Create new bot object
    */
    static public function create( $ALog )
    {
        return new WebBot( $ALog );
    }



    /*
        Start request
    */
    public function execute()
    {
        $URL = $this -> getUrl() -> toString();
        $this -> Log -> Begin() -> Param( 'URL', $URL );
        $Handle = curl_init();

        curl_setopt( $Handle, CURLOPT_URL, $URL );


        /* Build POST parameters  for CURL */
        $Keys = [];
        foreach( $this -> Post -> GetParams() as $Key => $Value )
        {
            switch( gettype( $Value ))
            {
                /* Bool value converted to string */
                case 'bool':
                    $Value = $Value ? 'true' : 'false';
                break;
                /* Array or object values converted to json */
                case 'array':
                case 'object':
                    $Value = json_encode
                    (
                        $Value,
                        JSON_UNESCAPED_UNICODE|
                        JSON_UNESCAPED_SLASHES
                    );
                break;
            }
            /* Key and value converted to URI */
            array_push
            (
                $Keys,
                encodeURIComponent( $Key ) . '=' . encodeURIComponent( $Value )
            );
        }
        $ParamsString = implode( '&', $Keys );

        /* Set post URL for CURL */
        if( !empty( $ParamsString ))
        {
            curl_setopt( $Handle, CURLOPT_POST, true);
            curl_setopt( $Handle, CURLOPT_POSTFIELDS, $ParamsString );
        }

//        curl_setopt( $Handle, CURLOPT_MAXCONNECTS, 1 );
//        curl_setopt( $Handle, CURLOPT_FORBID_REUSE, 1 );

        curl_setopt( $Handle, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $Handle, CURLOPT_NOSIGNAL, 1);
        curl_setopt( $Handle, CURLOPT_TIMEOUT_MS, $this -> RequestTimeoutMls );

        /* Send request */
        $this -> Content = curl_exec( $Handle );

        /* Error processing */
        $error = curl_error( $Handle );
        if( !empty( $error ))
        {
            $this -> setResult( 'web_bot/RequestError', [ 'Message' => $error ] );
        }

        /* Set content type */
        $this -> contentType = curl_getinfo( $Handle, CURLINFO_CONTENT_TYPE );

        /* Closw curl */
        curl_close( $Handle );

        if( $this -> isOk() && $this -> DumpContent )
        {
            $this -> getLog() -> dump( $this -> getContent());
        }

        $this -> Log -> End();
        return $this;
    }



    public function getContent()
    {
        return $this -> Content;
    }



    public function getGet()
    {
        return $this -> Get;
    }



    public function getPost()
    {
        return $this -> Post;
    }



    public function decodeJSON()
    {
        if( $this -> IsOk() )
        {
            if( empty( $this -> Content ))
            {
                $this -> Answer =
                [
                    'result' => [ 'code' => 'web_bot/empty_content' ]
                ];
            }
            else
            {
                $this -> Answer = json_decode
                (
                    $this -> Content,
                    true
                );
                if( empty( $this -> Answer ))
                {
                    $this -> setResult
                    (
                        'web_bot/json_error',
                        [
                            'url' => $this -> getUrl() -> toString(),
                            'content' => $this -> Content
                        ]
                    );
                }
            }
        }
        return $this;
    }



    public function getAnswer()
    {
        return empty( $this -> Answer ) ? $this -> Content : $this -> Answer;
    }



    public function decodeDOM()
    {
        if( $this -> IsOk() )
        {
            $Lines = explode( PHP_EOL, $this -> Content );
            $this -> Answer = new DOMDocument();

            $Last = libxml_use_internal_errors(true);
            $this -> Answer -> loadHTML( $this -> Content );

            foreach ( libxml_get_errors() as $error)
            {
                $Line   = $Lines[ $error -> line - 1];
                $Before = substr( $Line, $error -> column - 30, 30 );
                $Place  = substr( $Line, $error -> column, 1 );
                $After  = substr( $Line, $error -> column + 1, 30 );

                $this -> Log
                -> Warning( 'DOM Error' )
                -> Param( 'Line',       $error -> line )
                -> Param( 'Position',   $error -> column )
                -> Param( 'Message',    $error -> message )
                -> Info()
                -> Text( $Before )
                -> Text( $Place, TLog :: ESC_INK_RED )
                -> Text( $After );
            }
            libxml_use_internal_errors( $Last );
        }
        return $this;
    }



    public function checkDOMTagsValue
    (
        $ATag,
        $AValue
    )
    {
        $Result = false;
        $Nodes = $this -> Answer -> getElementsByTagName( $ATag );
        foreach( $Nodes as $Node) $Result = $Result || $Node -> textContent;
        return $Result;
    }



    public function checkDOMTagsExists
    (
        $ATag
    )
    {
        $Nodes = $this -> Answer -> getElementsByTagName( $ATag );
        return ! empty( $Nodes ) && count( $Nodes ) > 0;
    }



    public function setGetParams
    (
        $AParams
    )
    {
        $this -> Get -> SetParams( $AParams );
        return $this;
    }



    public function setPostParams
    (
        $AParams
    )
    {
        $this -> Post -> SetParams( $AParams );
        return $this;
    }



    public function setPostParam
    (
        $AKey,
        $AValue
    )
    {
        $this -> Post -> SetParam( $AKey, $AValue );
        return $this;
    }



    public function setUrl
    (
        $aUrl
    )
    {
        $this -> Url = $aUrl;
        return $this;
    }



    public function getUrl()
    {
        return $this -> Url;
    }



    public function getLog()
    {
        return $this -> Log;
    }



    public function getRequestTimeoutMls()
    {
        return $this -> RequestTimouteMls;
    }



    public function setRequestTimeoutMls
    (
        int $aValue = 1000
    )
    {
        $this -> RequestTimeoutMls = $aValue;
        return $this;
    }



    public function getDumpContent()
    {
        return $this -> DumpContent;
    }



    public function setDumpContent
    (
        bool $aValue = false
    )
    {
        $this -> DumpContent = $aValue;
        return $this;
    }



    public function getContentType()
    {
        return $this -> contentType;
    }
}

