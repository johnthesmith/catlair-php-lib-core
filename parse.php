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
    Requirements:
        php-yaml
        php-json
*/



namespace catlair;



/* Core libraries */
require_once( 'result.php' );



/*
    Parsing content from yaml json
*/
function clParse
(
    /* Body of content */
    string $aContent,
    /* Type of content yaml, yml, json */
    string $aType,
    /* Result object */
    Result &$aResult
)
:array
{
    /* Result array */
    $result = [];

    switch( $aType )
    {
        case 'yml':
        case 'yaml':
        {
            if( ! function_exists( 'yaml_parse' ))
            {
                $aResult -> setResult
                (
                    'yaml-extension-missing',
                    [ 'message' => 'YAML PECL extension is not installed' ]
                );
            }
            else
            {
                set_error_handler
                (
                    function( $code, $message ) use ( &$aResult )
                    {
                        $aResult -> setResult
                        (
                            'yaml-parse-error',
                            [ 'message' => $message ]
                        );
                        return true;
                    }
                );
                $result = yaml_parse( $aContent );
                restore_error_handler();
            }
        }
        break;
        case 'json':
        {
            $result = json_decode( $aContent, true );
            if( json_last_error())
            {
                $aResult -> setResult
                (
                    'json-parse-error',
                    [ 'message' => json_last_error_msg() ]
                );
            }
        }
        break;
        default:
        {
            $aResult -> setResult
            (
                'unknown-type-for-parsing',
                [ 'type' => $aType ]
            );
        }
        break;
    }

    return $result ?? [];
}
