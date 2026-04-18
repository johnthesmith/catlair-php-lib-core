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



require_once 'url.php';



class WebBot extends Result
{
    const HTTP      = 'http';
    const HTTPS     = 'https';

    /* Log object */
    private $log                = null;

    /* Url object */
    private $url                = null;
    /* Post params */
    private $post               = [];
    /* Response content */
    private $content            = null;
    /* Body reguest prefers befor post params */
    private $body               = null;
    /* HTTP method */
    private $method             = 'GET';
    /* Request headers */
    private $requestHeaders     = [];
    /* Headers input buffer */
    private $responseHeaders    = [];
    /* Request timeot mls */
    private $requestTimeoutMls  = 1000;
    /* Connect timeot mls */
    private $connectTimeoutMls  = 1000;



    /*
        Constructor
    */
    function __construct
    (
        /* Log object */
        $aLog
    )
    {
        $this -> log    = $aLog;
        $this -> url    = Url::create();
    }



    /*
        Create new bot object
    */
    static public function create
    (
        /* Log object */
        $aLog
    )
    {
        return new WebBot( $aLog );
    }



    /*
        Start request
    */
    public function execute()
    :self
    {
        $url = $this -> getUrl() -> toString();
        $this
        -> log
        -> begin()
        -> param( 'url', $url )
        -> param( 'method', $this -> method );

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

            /* Let and build header parameter */
            $headers = $this -> requestHeaders;

            /* Определение body */
            if( !empty( $this -> body ))
            {
                $body = $this -> body;
            }
            elseif( !empty( $this -> post ))
            {
                $body = http_build_query( $this -> post );
                $headers[ 'Content-Type' ] = 'application/x-www-form-urlencoded';
            }
            else
            {
                $body = null;
            }

            /* Определение размера body */
            if( empty( $body ))
            {
                unset( $headers[ 'Content-Length' ]);
            }
            else
            {
                $headers[ 'Content-Length' ] = (string) strlen( $body );
                curl_setopt( $handle, CURLOPT_POSTFIELDS, $body );
            }

            $curlHeaders = [];
            foreach( $headers as $name => $value )
            {
               $curlHeaders[] = $name . ': ' . $value;
            }

            /*  Set curl options */
            curl_setopt_array
            (
                $handle,
                [
                    CURLOPT_URL => $url,
                    CURLOPT_ENCODING => '',
                    CURLOPT_HEADER => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_NOSIGNAL => 1,
                    CURLOPT_TIMEOUT_MS => $this -> requestTimeoutMls,
                    CURLOPT_CONNECTTIMEOUT_MS => $this -> connectTimeoutMls,
                    CURLOPT_FORBID_REUSE => true,
                    CURLOPT_FRESH_CONNECT => true,
                    CURLOPT_CUSTOMREQUEST => $this -> getMethod(),
                    CURLOPT_HTTPHEADER => $curlHeaders
                ]
            );

            /*
                Send request
            */
            $response = curl_exec( $handle );

            /* Error processing */
            $error = curl_error( $handle );
            if( !empty( $error ))
            {
                $this -> setResult
                (
                    'request-error',
                    [
                        'msg' => $error,
                        'url' => $url
                    ]
                );
            }
            else
            {
                /* Separate headers and body */
                $headerSize = curl_getinfo( $handle, CURLINFO_HEADER_SIZE );
                $rawHeaders = substr( $response, 0, $headerSize );
                $this -> content = substr( $response, $headerSize );

                /* Headers parsing */
                $this -> responseHeaders = [];
                $lines = explode( "\r\n", trim( $rawHeaders ) );
                foreach( $lines as $line )
                {
                    $parts = explode( ':', $line, 2 );
                    $this -> responseHeaders[ trim( $parts[ 0 ])] = trim( $parts[ 1 ] ?? '' );
                }
            }

            /* Closw curl */
            curl_close( $handle );
        }
        $this -> log -> end();
        return $this;
    }





    public function decodeDOM()
    :self
    {
        if( $this -> IsOk() )
        {
            $Lines = explode( PHP_EOL, $this -> content );
            $this -> answer = new DOMDocument();

            $Last = libxml_use_internal_errors( true );
            $this -> answer -> loadHTML( $this -> content );

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



    public function decodeJSON()
    :self
    {
        if( $this -> isOk() )
        {
            if( empty( $this -> content ))
            {
                $this -> answer =
                [
                    'result' =>
                    [
                        'code' => 'web-bot/empty-content'
                    ]
                ];
            }
            else
            {
                $this -> answer = json_decode
                (
                    $this -> content,
                    true
                );
                if( empty( $this -> answer ))
                {
                    $this -> setResult
                    (
                        'web-bot/json-error',
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



    /**************************************************************************
    */


    /*
        Return result content
    */
    public function getContent()
    {
        return $this -> content;
    }




    public function getPost()
    :array
    {
        return $this -> post;
    }




    public function setPost
    (
        array $a
    )
    :self
    {
        $this -> post = $a;
        return $this;
    }



    public function getAnswer()
    :array|string
    {
        return empty( $this -> answer ) ? $this -> content : $this -> answer;
    }



    public function setUrl
    (
        $a
    )
    :self
    {
        $this -> url = $a;
        return $this;
    }



    public function getUrl()
    :Url
    {
        return $this -> url;
    }



    public function getLog()
    :Log
    {
        return $this -> Log;
    }



    public function getRequestTimeoutMls()
    :int
    {
        return $this -> requestTimouteMls;
    }



    public function setRequestTimeoutMls
    (
        int $a
    )
    :self
    {
        $this -> requestTimeoutMls = $a;
        return $this;
    }



    public function getConnectTimeoutMls()
    :int
    {
        return $this -> connectTimouteMls;
    }



    public function setConnectTimeoutMls
    (
        int $a
    )
    :self
    {
        $this -> connectTimeoutMls = $a;
        return $this;
    }



    /*
        Исходящий тип контента
    */
    public function setRequestContentType
    (
        string $contentType
    )
    :self
    {
        $this -> setHeader( 'Content-Type', $contentType );
        return $this;
    }



    /*
        Входящий тип конента
    */
    public function getResponseContentType()
    :?string
    {
        return $this -> responseHeaders[ 'Content-Type' ] ?? null;
    }



    /*
        Set http method
    */
    public function setMethod
    (
        /* Method name GET POST etc... */
        string $a
    )
    :self
    {
        $this -> method = $a;
        return $this;
    }



    /*
        Get http method
    */
    public function getMethod()
    :string
    {
        return $this -> method;
    }



    /*
        Set http headers key => value
    */
    public function setRequestHeaders
    (
        /* Array of headers key:val */
        array $a
    )
    :self
    {
        $this -> requestHeaders = $a;
        return $this;
    }



    /*
        Return request headers
    */
    public function getRequestHeaders()
    /* Array header:value */
    :array
    {
        return $this -> requestHeaders;
    }




    public function getResponseHeaders()
    :array
    {
        return $this -> responseHeaders;
    }



    /*
        Set raw body for request
    */
    public function setBody
    (
        /* Raw body*/
        string $a
    )
    :self
    {
        $this -> body = $a;
        return $this;
    }
}

