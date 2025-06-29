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
    Miscellaneous utilities
*/



/*
    Information volume conversion from count of bytes to string
*/
function clSizeToStr
(
    /* Count of bytes */
    int      $ADelta,
    /* A Zero value */
    string  $AZero = '0'
)
{
     if     ($ADelta >= 1024*1024*1024*1024)
        $r=round($ADelta/(1024*1024*1024*1024),2) . 'TiB';
     elseif ($ADelta >= 1024*1024*1024)
        $r=round($ADelta/(1024*1024*1024),2) . 'GiB';
     elseif ($ADelta >= 1024*1024)
        $r=round($ADelta/(1024*1024),2) . 'MiB';
     elseif ($ADelta >= 1024)
        $r=round($ADelta/1024,2).'KiB';
     elseif ($ADelta > 0.1 && !$AZero)
        $r=$ADelta . 'B';
     else
        $r=$AZero;

     return $r;
}



/*
    Analog for JS encodeURIComponent
*/
function encodeURIComponent
(
    $str
)
:string
{
    $revert = array( '%21' => '!', '%2A' => '*', '%28' => '(', '%29' => ')' );
    $r = strtr( rawurlencode( $str ), $revert );
    $r = strtr( $r, chr( 39 ), '%27' );
    return $r;
}



/*
    Return UUID string
*/
function clUUID()
:string
{
    $data = random_bytes(16);

    /* версия 4 */
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);

    /* вариант RFC 4122 */
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf
    (
        '%s%s-%s-%s-%s-%s%s%s',
        str_split( bin2hex( $data ), 4 )
    );
}



/*
    Return base64-encoded 128-bit ID
*/
function clBase64ID()
:string
{
    return rtrim( base64_encode( random_bytes(16) ), '=' );
}



/*
    Return true if string is guid
*/
function clIsGUID
(
    string $AValue
)
:bool
{
    return preg_match
    (
        '/^[a-f0-9]{8}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{12}$/',
        $AValue
    );
}



/*
    Generate the random identifier
*/
function clRndID
(
    $ALength = 16,
    $AString = 'ABDEFHKLMNPRSTUXYZ'
)
{
    $Length = strlen( $AString ) - 1;
    $Result = '';
    for( $i=0; $i<$ALength; $i++ )
    {
        $Result .= $AString[ rand(0, $Length) ];
    }
    return $Result;
}



/*
    Создание случайного инденификатора
*/
function clRndNum( $ALength )
{
    return clRndID( $ALength, '0123456789' );
}



function clSeries( $AMasq )
{
    $Result = '';
    $Chains = explode( ';', $AMasq );

    foreach ( $Chains as $Chain )
    {
        $Param = explode( '=', $Chain );
        if ( count( $Param ) > 1 )
        {
            switch( $Param[ 0 ])
            {
                case 'GUID':
                    $Part = clUUID();
                break;
                case 'Now':
                    $Now = new TMoment();
                    $Part = $Now -> Now() -> ToString( $Param[ 1 ]);
                break;
                case 'String':
                    $Part = clRndID( (int) $Param[ 1 ]);
                break;
                case 'Number':
                    $Part = clRndNum( (int) $Param[ 1 ]);
                break;
                default:
                    $Part = $Param[ 0 ];
                break;
            }
        }
        else
        {
            $Part = $Param[ 0 ];
        }
        $Result = $Result . $Part;
    }

    if ( $Result == '' ) $Result = clUUID();
    return $Result;
}



/*
    Path control. Use for remove dangerous sequences from path.
*/
function clPathControl( $APath )
{
    return str_replace
    (
        [ '\\', '/../', '\\..\\', '/./', '?', '*', '$', '[', ']', ' ', '>', '<', '|', ':', ';' ],
        [ '_',  '_',   '_',       '_',   '_', '_', '_', '_', '_', '_', '_', '_', '_', '_', '_' ],
        $APath
    );
}



/*
    File control. Use for remove dangerous sequences from file name.
*/
function clFileControl( $AFile )
{
    return str_replace
    (
        [ '/', '&', '..', '?', '"', '*', ' ', '$', '[', ']', '\\', '<', '>', '|', '=', ':', ';' ],
        [ '_', '_', '_',  '_', '_', '_', '_', '_', '_', '_', '_',  '_', '_', '_', '_', '_', '_' ],
        $AFile
    );
}



/*
    Scatter string $AName = ABCD to path /A/B/C/ABCD with $ADepth for $ACharSet
*/
function clScatterName
(
    string  $AName,
    int     $ADepth     = 3,
    string  $ACharSet   = 'UTF-8'
)
{
    $Result = '';
    $l = mb_strlen( $AName, $ACharSet );
    for( $i=0; $i<$ADepth && $i<$l; $i++)
    {
        $Result .= '/' . mb_substr( $AName, $i, 1, $ACharSet );
    }
    return $Result . '/' . $AName;
}



/*
    Удаление папки рекурсивное
*/
function clDeleteFolder( $APath )
{
    if( is_dir( $APath ) === true )
    {
        $files = array_diff(scandir($APath), array('.', '..'));
        foreach( $files as $file)
        {
            clDeleteFolder(realpath($APath) . '/' . $file );
        }
        return rmdir($APath);
    }
    else
    {
        if( is_file( $APath ) === true ) return unlink( $APath );
    }
    return !file_exists( $APath );
}



function clStringToHex
(
    $AString
)
{
    $hex="";
    for ($i=0; $i < strlen($AString); $i++) $hex .= dechex(ord($AString[$i]));
    return $hex;
}



function clHexToString
(
    $hex
)
{
    $string="";
    for ($i=0; $i < strlen($hex)-1; $i+=2) $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    return $string;
}



/*
    Copy file with subfolders
*/
function clFileCopy
(
    $src,
    $dst
)
{
    $dir = opendir( $src );
    @mkdir( $dst );

    while( false !== ( $file = readdir( $dir )) )
    {
        if (( $file != '.' ) && ( $file != '..' ))
        {
            if ( is_dir($src . '/' . $file) )
                clFileCopy($src . '/' . $file, $dst . '/' . $file);
            else
                copy($src . '/' . $file, $dst . '/' . $file);
        }
    }
    closedir($dir);
}



/*
    Convert dirt string #tAg1 qwe #TAG2 to #tag1 #tag2
*/
function clStringToTag($AString)
{
    return ( $AString == null )
    ? ''
    : preg_replace
    (
        '/ ?#END \Z/',
        '',
        preg_replace('/(?:.|\n)*?(#\w+)/','$1 ',$AString.'#END')
    );
}




/*
    Checks or creates path if that not exists
*/
function clCheckPath
(
    /* Puth */
    string  $aPath,
    /* Rights */
    int     $aRights = 0777
)
:bool
{
    if( !file_exists( $aPath ))
    {
        @mkdir( $aPath, $aRights, true );
    }
    return file_exists( $aPath );
}



/*
    Content from object
*/
function clObjectToContent
(
    $AObject,
    $AContent
)
{
    foreach( $AObject as $Key => $Value)
    {
        $Type = gettype( $Value );
        switch ( $Type )
        {
            case 'boolean':
            case 'integer':
            case 'double':
            case 'string':
                $AContent = str_replace( '%' . $Key . '%', $Value, $AContent );
            break;
            case 'array':
            case 'object':
                $AContent = clObjectToContent($Value, $AContent);
            break;
            case 'NULL':
               $AContent = str_replace( '%' . $Key . '%', 'null', $AContent );
            break;
        }
    }
    return $AContent;
}



/*
    Replaces all values in Source using an array of replacement pairs
*/
function clReplace
(
    /* Source content for raplace */
    string $ASource,
    /* Array of pairs key => value */
    array  $AReplace,
    /* Begin of macro */
    string $ABegin      = '%',
    /* EndOfMacro */
    string $AEnd        = '%',
    /* List of exclude Keynames */
    array  $AExclude    = [],
    /* Callback function(string $AKeyName) return KeyValue */
    $ACallback          = null
)
{
    /* Move Source to Result */
    $Result = $ASource;

    $count = 0;

    do
    {
        /* Split the source to lexemes */
        $Source = preg_split
        (
            '/(' .$ABegin. '\w*' . $AEnd . ')/',
            $Result,
            0,
            PREG_SPLIT_DELIM_CAPTURE
        );

        /* Loop for split result */
        $Continue = false;
        foreach( $Source as $Lexeme )
        {
            /* Check the lexeme on exclude list */
            $ExcludeFlag = false;

            foreach( $AExclude as $Item )
            {
                $ExcludeFlag
                = $ExcludeFlag || ( $ABegin . $Item . $AEnd == $Lexeme );
            }

            /* Replace for unexcluded */
            if( ! $ExcludeFlag )
            {
                if
                (
                    preg_match
                    (
                        '/' . $ABegin. '\w*' . $AEnd .  '/', $Lexeme
                    )
                )
                {
                    $ResultBefore = $Result;

                    /* Define name */
                    $Name = str_replace
                    (
                        [ $ABegin, $AEnd ],
                        [ '','' ],
                        $Lexeme
                    );

                    /* Define value */
                    $Value = null;
                    if( array_key_exists( $Name, $AReplace ) )
                    {
                        /* Get value from parameters */
                        $Value = $AReplace[ $Name ];
                    }
                    elseif( ! empty( $ACallback ))
                    {
                        /* Get value from callback function by Name */
                        $Value = call_user_func( $ACallback, $Name );
                    }

                    /* Replace */
                    if( $Value !== null )
                    {
                        $Result = str_replace( $Lexeme, $Value, $Result );
                    }

                    /* Continue while result not equal previous */
                    $Continue = $Continue || $ResultBefore != $Result;
                }
            }
        }
        $count ++;
        if( $count > 100 )
        {
            $Continue = false;
        }
    }
    while( $Continue );

    return $Result;
}



/*
    Выполняет рекурсивуню подмену макроподстановок в Source
    возвращается результат для которого все значения замененены.
    Параметры заменяются с использованием открывающих изакрывающих ключей.
    Чувствительно к регистру
*/
function clPrep
(
    /* Значение для макроподстановок */
    $ASource,
    /* Список ключей для подмены */
    array   $AKeys      = [],
    /* Список игнорируемых ключеней, которые остаются в незменном виде */
    array   $AExclude   = [],
    /* Открывающий ключ макроподстановки. Только 1 символ */
    string  $ABegin     = '%',
    /* Закрывающий ключ макроподстановки. Только 1 символ */
    string  $AEnd       = '%'
)
{
    $Result = null;
    switch( gettype( $ASource ))
    {
        case 'string':
            $Result = clReplace
            (
                $ASource,
                $AKeys,
                $ABegin,
                $AEnd,
                $AExclude
            );
        break;
        case 'array':
            $Result = [];
            foreach( $ASource as $Key => $Value )
            {
                $Result[ $Key ] = clPrep
                (
                    $Value,
                    $AKeys,
                    $AExclude,
                    $ABegin,
                    $AEnd
                );
            }
        break;
        default:
            $Result = $ASource;
        break;
    }

    return $Result;
}





/*
    Remove tags from HTML text in $ABody and cut the string to $ALength
    Return preview text
*/
function clPreview
(
    /* Text with HTML tags */
    $ABody,
    /* Max length of result */
    $ALength
)
{
    return mb_substr
    (
        preg_replace
        (
            '/(\<(\/?[^>]+)>)/',
            '', $ABody
        ), 0, $ALength
    ) . '...';
}



function clCheck
(
    $AValue,
    $ATrue = null,
    $AFalse = null
)
{
     if ( empty ( $AValue) )
     {
        if ( $AFalse == null ) $Result = null;
        else $Result = $AFalse;
     }
     else
     {
        if ( $ATrue == null ) $Result = $AValue;
        else $Result = $ATrue;
     }
     return $Result;
}



/*
    Set key to object
*/
function clValueToObject
(
    /* Array or object */
    &$AObject,
    /* The String Key or array of sting like the path */
    $AKey,
    /* Value for key */
    $AValue = null
)
{
    switch( gettype( $AKey ))
    {
        case 'array':
            $c = count( $AKey );
            $iObject = &$AObject;
            for( $i = 0; $i < $c; $i++ )
            {
                $iKey = $AKey[ $i ];
                if( $i < $c - 1 )
                {
                    switch( gettype( $iObject ))
                    {
                        case 'array':
                            if(array_key_exists( $iKey, $iObject ))
                            {
                                $iObject = &$iObject[ $iKey ];
                            }
                            else
                            {
                                /* Create new node */
                                $iObject[ $iKey ] = [];
                                $iObject = &$iObject[ $iKey ];
                            }
                        break;
                        case 'object':
                            if( property_exists( $iObject, $iKey ))
                            {
                                $iObject = &$iObject -> $iKey;
                            }
                            else
                            {
                                /* Create new node */
                                $iObject -> $iKey = [];
                                $iObject = &$iObject -> $iKey;
                            }
                        break;
                        default:
                            /* Create new node */
                            $iObject[ $iKey ] = [];
                            $iObject = &$iObject[ $iKey ];
                        break;
                    }
                }
                else
                {
                    switch( gettype( $iObject ))
                    {
                        case 'array':
                            $iObject[ $iKey ] = $AValue;
                        break;
                        case 'object':
                            $iObject -> $iKey = $AValue;
                        break;
                    }
                }
            }
        break;
        default:
            switch ( gettype( $AObject ))
            {
                case 'array':
                    $AObject[ $AKey ] = $AValue;
                break;
                case 'object':
                    $AObject -> $AKey = $AValue;
                break;
            }
        break;
    }
    return true;
}



/*
    Return key from object
*/
function clValueFromObject
(
    /* Array or object */
    $AObject,
    /* Key as string or as array of sting */
    $AKey,
    /* Default value */
    $ADefault = null
)
{
    $Result = $ADefault;
    switch( gettype( $AKey ))
    {
        case 'array':
            if( count( $AKey ) > 1 )
            {
                $Key = array_shift( $AKey );
                $Object = clValueFromObject( $AObject, $Key, $ADefault );
                $Result = clValueFromObject( $Object,  $AKey, $ADefault);
            }
            else
            {
                $Result = clValueFromObject( $AObject, $AKey[ 0 ], $ADefault );
            }
        break;
        default:
            switch ( gettype( $AObject ))
            {
                case 'array':
                    $Result
                    = array_key_exists( $AKey, $AObject )
                    ? $AObject[ $AKey ]
                    : $ADefault;
                break;
                case 'object':
                    $Result = property_exists( $AObject, $AKey )
                    ? $AObject -> $AKey
                    : $ADefault;
                break;
            }
        break;
    }
    return $Result;
}



/*
    Check exists key in array or object
*/
function clValueExists
(
    $AObject,           /* Array or object */
    $AKey               /* Key as string or as array of sting */
)
{
    $Result = false;
    switch( gettype( $AKey ))
    {
        case 'array':
            if( count( $AKey ) > 1 )
            {
                $Key = array_shift( $AKey );
                $Object = clValueFromObject( $AObject, $Key );
                $Result = clValueFromObject( $Object, $AKey );
            }
            else
            {
                $Result = clValueExists( $AObject, $AKey[ 0 ] );
            }
        break;
        default:
            switch ( gettype( $AObject ))
            {
                case 'array':
                    $Result = array_key_exists( $AKey, $AObject );
                break;
                case 'object':
                    $Result = property_exists( $AObject, $AKey );
                break;
            }
        break;
    }
    return $Result;
}






/*
    Convert Value of any type in to Float
*/
function clValueToFloat( $AValue, $AThousandDelimeter = ' ' )
{
    $Type = gettype( $AValue );
    switch( $Type )
    {
        case 'integer':
        case 'boolean':
            $Value = (float) $AValue;
        break;

        case 'double':
            $Value = $AValue;
        break;

        case 'string':
            $Value = (float) str_replace( $AThousandDelimeter, '', $AValue );
        break;

        default:
            $Value = 0.0;
        break;
    }
    return $Value;
}




/*
    Recursion runing the files tree
    TODO - move it at file_utils.php
*/
function clFileScan
(
    $APath,
    $AOnFolder  = null,
    $AOnFile    = null,
    $AIndex     = 0
)
{
    if( is_dir( $APath ))
    {
        /* Path is directory */
        $Dir = @opendir( $APath );
        if( $Dir !== false )
        {
            while(( $File = @readdir( $Dir )) !== false )
            {
                if(( $File != '.' ) && ( $File != '..' ))
                {
                    $Full = $APath . '/' . $File;
                    if( is_dir( $Full ))
                    {
                        if
                        (
                            $AOnFolder == null
                            ? true
                            : call_user_func( $AOnFolder, $Full, $AIndex )
                        )
                        {
                            /* Recursion */
                            clFileScan
                            (
                                $Full,
                                $AOnFolder,
                                $AOnFile,
                                $AIndex + 1
                            );
                        }
                    }
                    else
                    {
                        call_user_func( $AOnFile, $Full, $AIndex );
                    }
                }
            }
            closedir( $Dir );
        }
    }
    else
    {
        /* Path is file */
        if( file_exists( $APath ))
        {
            call_user_func( $AOnFile, $APath, $AIndex );
        }
    }

    return true;
}



/*
    Wrap the Body between the prefix and suffix
    or return the empty string if result does not exists.
*/
function clWrap
(
    string  $ABody   = null,
    string  $APrefix = null,
    string  $ASuffix = null
)
{
    return empty( $ABody ) ? '' :
    (
        ( empty( $APrefix ) ? '' : ( $APrefix . ' ' ) ) .
        $ABody .
        ( empty( $ASuffix ) ? '' : ( ' ' . $ASuffix ) )
    );
}



/*
    Check the File Name in including and excluding list
*/
function clFileMatch
(
    string  $AFileName,
    array   $AIncludes = [],
    array   $AExcludes = []
)
{
    $IncludeFlag = false;
    foreach( $AIncludes as $Pattern )
    {
        $IncludeFlag = $IncludeFlag || fnmatch( $Pattern, $AFileName );
    }

    $ExcludeFlag = false;
    foreach( $AExcludes as $Pattern )
    {
        $ExcludeFlag = $ExcludeFlag || fnmatch( $Pattern, $AFileName );
    }

    return
    ( empty( $AIncludes ) || $IncludeFlag ) &&
    ( empty( $AExcludes ) || !$ExcludeFlag );
}




/*
    Convert value to typed value
*/
function clConvert
(
    /* Типизируемое значение */
    $AValue,
    /* Тип в соответсвии с результатом gettype() */
    string $AType = 'string'
)
{
    /* Приведение типа */
    $Result = $AValue;
    switch( $AType )
    {
        case 'integer':
            $Result = (int) $AValue;
        break;
        case 'boolean':
            $Result = $AValue === true ||
            $AValue === 'true' ||
            $AValue === 'on' ||
            $AValue == 1.0;
        break;
        case 'double':
            $Result = (float) $Result;
        break;
        case 'string':
            /* Конвертация в строку */
            switch( gettype( $AValue ))
            {
                case 'boolean':
                case 'integer':
                case 'double':
                case 'array':
                case 'object':
                    $Result = json_encode( $AValue );
                break;
            }
        break;
        case 'object':
        case 'array':
        case 'NULL':
           /* Не типизируется, возвращается в исходном состоянии */
        break;
    }
    return $Result;
}



/*
    kebab-case to UpperCamelCase
        kebab-case -> KebabCase
*/
function clKebabToUpperCamel
(
    string $input
)
: string
{
    return str_replace( ' ', '', ucwords( str_replace( '-', ' ', $input )));
}



/*
    Конвертакция стиля UpperCamelCase в SnakeCase
    - UpperCamelCase -> upper_camel_case
*/
function clUpperCamelToSnake( $AValue )
{
    return str_replace
    (
        ' ',
        '',
        strtolower
        (
            preg_replace('/(?<!^)[A-Z]/', '_$0', $AValue )
        )
    );
}



/*
    Конвертакция стиля UpperCamelCase в SnakeCase
    - UpperCamelCase -> CONSTSNT_CASE
    - HELLP_WORLD
*/
function clUpperCamelToConstant( $AValue )
{
    return
    str_replace
    (
        ' ',
        '',
        strtoupper( preg_replace('/(?<!^)[A-Z]/', '_$0', $AValue ))
    );
}



/*
    Конвертакция стиля snake_case в UpperCamelCase
*/
function clSnakeToUpperCamel( $AValue )
{
    return str_replace( ' ', '', ucwords( str_replace( '_', ' ', $AValue )));
}



/*
    Converts snake_case snake_case and CONSTANT_CASE to UpperCamelCase
*/
function clToUpperCamel( $AValue )
{
    $Upper = strtoupper( $AValue );
    $Lower = strtolower( $AValue );

    /* CONSTANT_CASE */
    if( $Upper == $AValue ) $Result = clSnakeToUpperCamel( $Lower );
    /* snake_case */
    elseif ( $Lower == $AValue ) $Result = clSnakeToUpperCamel( $Lower );
    /* Default */
    else {
    $Result = $AValue;
    }

    return $Result;
}





/*
    Remove comments from source
*/
function clPurgeComments
(
    $ASource    /* Source with comments */
)
{
    $ASource = preg_replace('/\/\/.+/', '', $ASource );
    return $ASource;
}



/*
    Align the text
    Source https://stackoverflow.com/questions/14773072/php-str-pad-unicode-issue
*/
function strPad
(
    /* Soucre string */
    string  $AString    = null,
    /* Result length */
    int     $ALength    = 0,
    /* ParString */
    string  $APad       = ' ',
    /* Aligment Type like str_type pad_type argument */
    int     $AAligment  = STR_PAD_RIGHT,
    string  $AEncoding  = 'UTF-8'
)
: string
{
    $input_length = mb_strlen( $AString, $AEncoding );
    $pad_string_length = mb_strlen( $APad, $AEncoding );

    if( $ALength <= 0 || ($ALength - $input_length) <= 0 )
    {
        return $AString;
    }

    $num_pad_chars = $ALength - $input_length;

    switch( $AAligment )
    {
        case STR_PAD_RIGHT:
            $left_pad = 0;
            $right_pad = $num_pad_chars;
            break;

        case STR_PAD_LEFT:
            $left_pad = $num_pad_chars;
            $right_pad = 0;
            break;

        case STR_PAD_BOTH:
            $left_pad = floor( $num_pad_chars / 2 );
            $right_pad = $num_pad_chars - $left_pad;
            break;
    }

    $result = '';
    for ($i = 0; $i < $left_pad; ++$i)
    {
        $result .= mb_substr($APad, $i % $pad_string_length, 1, $AEncoding);
    }

    $result .= $AString;
    for ($i = 0; $i < $right_pad; ++$i)
    {
        $result .= mb_substr($APad, $i % $pad_string_length, 1, $AEncoding);
    }

    return $result;
}



/*
    Read hidden input from cli
*/
function clReadPassword()
{
    system( 'stty -echo' );
    $result = fgets( STDIN );
    system( 'stty echo' );
    print_r( PHP_EOL );
    return $result;
}





function clArrayMerge
(
    &$A1,
    &$A2
)
{
    $Result = $A1;

    foreach( $A2 as $key => &$value )
    {
        if
        (
            is_array( $value ) &&
            isset( $Result[ $key ]) &&
            is_array ( $Result[ $key ])
        )
        {
            $Result[ $key ] = clArrayMerge
            (
                $Result[ $key ],
                $value
            );
        }
        else
        {
            $Result[ $key ] = $value;
        }
    }

    return $Result;
}



/*

    Дополняет элементами второго массива первый с глубоким объединением
    массивов, без замены скалярных значений.

    Пример:
    $A1 = ['key1' => 'val1', 'key2' => ['sub1' => 1]];
    $A2 = ['key1' => 'new_val', 'key2' => ['sub2' => 2], 'key3' => 'val3'];
    Результат:
    [
        'key1' => 'val1',                   // не перезаписано
        'key2' => ['sub1'=>1, 'sub2'=>2],   // массивы объединены
        'key3' => 'val3'                    // добавлен новый ключ
    ];
*/
function clArrayAppend
(
    $A1,
    $A2
)
{
    $result = $A1;
    foreach( $A2 as $key => $value )
    {
        if( !isset( $result[ $key ]))
        {
            $result[ $key ] = $value;
        }
        else if
        (
            is_array( $result[ $key ]) &&
            is_array( $value )
        )
        {
            $result[ $key ] = clArrayAppend( $result[ $key ], $value );
        }
    }

    return $result;
}



/*
    Converts a given path to a canonical form and ensures it is within the
    allowed root directory. Returns the canonical path or false if invalid or
    outside the root directory.
*/
function clCanonicalPath
(
    string $userPath,
    string $rootPath
)
{
    $normalize = function($path)
    {
        $parts = [];
        foreach( explode('/', str_replace('\\', '/', $path )) as $part) {
            if( $part === '' || $part === '.' ) continue;
            if( $part === '..' ) array_pop( $parts );
            else $parts[] = $part;
        }
        return '/' . implode( '/', $parts );
    };

    /* Убедимся, что rootPath абсолютный */
    $root = rtrim( $normalize(realpath($rootPath)), '/' );

    /* Если пользовательский путь пустой, вернем false */
    if (empty($userPath))
    {
        return false;
    }

    /* Строим полный путь */
    $full
    = (isset($userPath[0]) && $userPath[0] === '/')
    ? $normalize($userPath)
    : $normalize($root . '/' . $userPath);

    /* Проверяем, что путь находится внутри корня */
    return ( strpos( $full, $root ) === 0) ? $full : false;
}



/*
    Returns the local path if available, or an empty value otherwise.
*/
function clLocalPath
(
    /* Local path */
    string $a = null
)
:string
{
    return  empty( $a ) ? '' : ( DIRECTORY_SEPARATOR . trim($a, "/\\") );
}
