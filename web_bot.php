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
    private $content            = null;
    private $Answer             = null; /* Object JSON, DOM, or other*/
    private $DumpContent        = false;
    private $RequestTimeoutMls  = 1000; /* Length of request at mls befor drop down it */
    private $contentType        = null;

    /* Headers input buffer */
    private $headers            = [];

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
        $url = $this -> getUrl() -> toString();
        $this -> Log -> Begin() -> Param( 'url', $url );

        if( !function_exists( 'curl_init' ))
        {
            $this -> setResult
            (
                'curl-sdk-not-found',
                [
                    'msg' => 'Need to install php_curl'
                ]
            );
        }
        else
        {
            $handle = curl_init();

            curl_setopt( $handle, CURLOPT_URL, $url );
            curl_setopt( $handle, CURLOPT_ENCODING, '');
            curl_setopt( $handle, CURLOPT_HEADER, true );
            curl_setopt( $handle, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $handle, CURLOPT_NOSIGNAL, 1);
            curl_setopt( $handle, CURLOPT_TIMEOUT_MS, $this -> RequestTimeoutMls );

            /* Build POST parameters  for CURL */
            $post = $this -> Post -> getParams();
            $headers = $this -> headers;

            unset( $this -> headers[ 'Content-Length' ]);

            if( !empty( $post ))
            {
                $Keys = [];
                $paramsStr = http_build_query( $post );
                curl_setopt( $handle, CURLOPT_POST, true);
                curl_setopt( $handle, CURLOPT_POSTFIELDS, $paramsStr );
                $headers[ 'Content-Type' ] = 'application/x-www-form-urlencoded';
                unset($headers['Content-Length']);
            }

            /* Let and build header parameter */
            $curlHeaders = [];
            foreach( $headers as $name => $value )
            {
               $curlHeaders[] = $name . ': ' . $value;
            }
            /* Apply headers */
            curl_setopt( $handle, CURLOPT_HTTPHEADER, $curlHeaders );

            /*
                Send request
            */
            $response = curl_exec( $handle );

            /* Separate headers and body */
            $headerSize = curl_getinfo( $handle, CURLINFO_HEADER_SIZE );
            $rawHeaders = substr( $response, 0, $headerSize );
            $this -> content = substr( $response, $headerSize );

            /* Headers parsing */
            $this -> headers = [];
            $lines = explode( "\r\n", trim( $rawHeaders ) );
            foreach( $lines as $line )
            {
                if( strpos($line, ':') !== false )
                {
                    $parts = explode(':', $line, 2 );
                    $this -> headers[ trim( $parts[ 0 ])] = trim( $parts[ 1 ]);
                }
            }

            /* Error processing */
            $error = curl_error( $handle );
            if( !empty( $error ))
            {
                $this -> setResult( 'request-error', [ 'msg' => $error ]);
            }

            /* Set content type */
            $this -> contentType = curl_getinfo( $handle, CURLINFO_CONTENT_TYPE );

            /* Closw curl */
            curl_close( $handle );

            if( $this -> isOk() && $this -> DumpContent )
            {
                $this -> getLog() -> dump( $this -> getContent());
            }
        }
        $this -> Log -> End();
        return $this;
    }



    public function getContent()
    {
        return $this -> content;
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
            if( empty( $this -> content ))
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
                    $this -> content,
                    true
                );
                if( empty( $this -> Answer ))
                {
                    $this -> setResult
                    (
                        'web_bot/json_error',
                        [
                            'url' => $this -> getUrl() -> toString(),
                            'content' => $this -> content
                        ]
                    );
                }
            }
        }
        return $this;
    }



    public function getAnswer()
    {
        return empty( $this -> Answer ) ? $this -> content : $this -> Answer;
    }



    public function decodeDOM()
    {
        if( $this -> IsOk() )
        {
            $Lines = explode( PHP_EOL, $this -> content );
            $this -> Answer = new DOMDocument();

            $Last = libxml_use_internal_errors(true);
            $this -> Answer -> loadHTML( $this -> content );

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
        array $a
    )
    :self
    {
        $this -> Post -> setParams( $a );
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



    /*
        Set http headers key => value
    */
    public function setHeaders
    (
        /* Массив заголовков (ключ => значение) */
        array $a
    )
    :self
    {
        $this -> headers = $a;
        return $this;
    }



    /*
        Set http headers key => value
    */
    public function getHeaders()
    :array
    {
        return $this -> headers;
    }
}

