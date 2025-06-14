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
    Class for typed parameters.

    Parameters are stored as key => value.
    Supports typed value retrieval, copying, and validation.

    Parameters inherit from Result and maintain state.
*/

/*
    Load core libraties
*/
require_once 'result.php';
require_once 'utils.php';



class Params extends Result
{
    /*
        Приватные свойства
    */
    /* Массив именованных параметров */
    private $Params         = [];
    /* Мавссив умолчальног пути параметров */
    private $CurrentPath    = [];


    /*
        Создает новый объект параметров и возвращает его
    */
    static public function define
    (
        array $AValues = []
    )
    {
        $Result = new Params();
        $Result -> setParams( $AValues );
        return $Result;
    }



    /*
        Устанавливает список параметров из массива или объекта
    */
    public function setParams
    (
        $AParams = []   /* Массив или объект */
    )
    {
        switch( gettype( $AParams ))
        {
            case 'array':
                $this -> Params = $AParams;
            break;
            case 'object':
                $this -> Params = (array) $AParams;
            break;
            default:
                $this -> Params = [];
            break;
        }
        return $this;
    }



    /*
        Возвращает список параметров
    */
    public function &getParams()
    {
        return $this -> Params;
    }



    /*
        Возвращает типизированное значение параметра по дереву или имени
    */
    public function getParam
    (
        /* Массив пути до параметра либо имя параметра на нулевом уровне */
        $APath,
        /*
            Умолчальное значение при отсутсвии параметра.
            Типизация производится по умолчальному значению
        */
        $ADefault = null
    )
    {
        $path = array_merge( $this -> CurrentPath, is_array( $APath ) ? $APath : [ $APath ]);
        /* Получение параметра */
        $Result = clConvert
        (
            clValueFromObject( $this -> Params, $path, $ADefault ),
            gettype( $ADefault )
        );
        return $Result;
    }



    /*
        Устанавливает значение параметр
    */
    public function setParam
    (
        $APath,             /* Массив пути до параметра или ключа параметра */
        $AValue     = null, /* Устанавливаемое значение */
        $ANoEmpty   = false /* Необязательное исключение пустого значения */
    )
    {
        if( !empty( $AValue ) || !$ANoEmpty )
        {
            $path = array_merge( $this -> CurrentPath, is_array( $APath ) ? $APath : [ $APath ]);
            clValueToObject( $this -> Params, $path, $AValue );
        }
        return $this;
    }




    /*
        Returns the parameter value by tree or name.
        Subsequent keys are not checked if a value was already found.
    */
    public function getParamMul
    (
        /*
            Array of paths or parameter names to search,
            or a top-level parameter name
        */
        array $aPathes,
        /*
            Default value if parameter is not found.
            Return type is inferred from the default value
        */
        $aDefault = null
    )
    {
        $result = null;
        $c = count( $aPathes );
        for( $i = 0; $i < $c && $result === null ; $i ++ )
        {
            $val = $this -> getParam( $aPathes[ $i ]);
            if( $val !== null )
            {
                $result = $val;
            }
        }
        return $result === null ? $aDefault : $result;
    }



    /*
        Добавляет параметры из массива или объекта
    */
    public function addParams
    (
        $AParams, /* Объект или массив, содержащие ключ=>значение*/
        bool $ANotExists = false
    )
    {
        $AParams = (array) $AParams;

        if( $ANotExists )
        {
            $this -> Params = clArrayMerge( $AParams, $this -> Params );
        }
        else
        {
            $this -> Params = clArrayMerge( $this -> Params, $AParams );
        }
        return $this;
    }



    /*
        Appends parameters from an array or object
        without overwriting existing scalar values.
    */
    public function appendParams
    (
        $AParams
    )
    {
        $AParams = (array) $AParams;

        $this -> Params = clArrayAppend
        (
            $this -> Params,
            $AParams
        );

        return $this;
    }



    /*
        Очищает список параметров
    */
    public function clear()
    {
        $this -> Params = [];
        return $this;
    }



    /*
        Удаляет параметр по имени
    */
    public function deleteParam
    (
        $AName /* Имя параметра */
    )
    {
        if( array_key_exists( $AName, $this -> Params )) unset( $this -> Params[ $AName ]);
        return $this;
    }



    /*
        Удаляет параметры по имени
    */
    public function deleteParams
    (
        array $AValues /* Массив ключей */
    )
    {
        foreach( $AValues as $Name )
        {
            $this -> deleteParam( $Name );
        }
        return $this;
    }



    /*
        Копирует параметр из источника в текущий объект по имени ключа
        а в случа отсутсвия устанавливает умолчальное значение
    */
    public function paramFrom
    (
        string $AKey,       /* Имя ключа */
        Params $ASource,    /* Источник */
        $ADefault = null    /* Умолчальное значение */
    )
    {
        return $this -> setParam( $AKey, $ASource -> getParam( $AKey, $ADefault ));
    }



    /*
        Возвращает перечень параметров в URL нотации
    */
    public function getParamsAsURL()
    {
        $Result = [];
        foreach( $this -> Params as $Key => $Value )
        {
            if( $Value !== null ) array_push(  $Result, $Key . '=' . urlencode( clConvert( $Value, 'string' )));
        }
        return implode( '&', $Result );
    }



    /*
        Конвертирует все параметры в массив строк
        Объеты и массивы конвертируются в json
        Логические значения в 'true' или 'false'
    */
    public function getParamsAsString()
    {
        $Result = [];
        foreach( $this as $Key => $Value )
        {
            if( $Value != null )
            {
                $Result[ $Key ] = urlencode( clConvert( $Value, 'string' ));
            }
        }
        return $Result;
    }


    /*
        Устанавливаются параметры из JSON cnhjrb
    */
    public function setParamsFromJSON
    (
        $AString
    )
    {
        if( !empty( $AString ))
        {
            $Params = json_decode( $AString );
            if( empty( $Params ))
            {
                $this -> SetCode( 'JSONDecodeError' );
            }
            else
            {
                $this -> SetParams( $Params );
            }
        }
        return $this;
    }



    /*
        Копирование списка параметров из источника в текущий объект
        Можно использовать только включаемые имена
    */
    public function copyFrom
    (
        Params $ASource,   /* Объект источник */
        array $ANames = []  /* Список включаемых имент */
    )
    {
        $Length = count( $ANames );
        foreach( $ASource -> Params as $Key => $Value )
        {
            if( in_array( $Key, $ANames ) || $Length == 0 )
            {
                $this -> SetParam( $Key, $Value );
            }
        }
        return $this;
    }



    /*
        Сравнивает именованный список параметров с содержимым объекта
        Если все параметры совпадают - возвращается истина
        Если переданный массив пуст, возвращается истина
    */
    public function paramsIsEqual
    (
        array $ANames = [], /* Array of names */
        $ADefault = null    /* Value for compare */
    )
    {
        $Result = true;
        if( count( $ANames ) > 0 )
        {
            $Value = empty( $ADefault ) ? $ANames[ 0 ] : $ADefault;
            foreach( $ANames as $Name )
            {
                $Result = $Result && $Value === $this -> GetParam( $Name );
            }
        }
        return $Result;
    }



    /*
        Возвращается истина если параметр равен null
    */
    public function paramIsNull( $AName )
    {
        return $this -> GetParam( $AName ) === null;
    }



    /*
        Врзвращается истина если параметр empty
    */
    public function paramIsEmpty( $AName )
    {
        return empty( $this -> getParam( $AName ));
    }



    /*
        Врзвращается истина если параметр существует
    */
    public function paramExists( $AName )
    {
        return array_key_exists( $AName, $this -> Params );
    }



    /*
        Устанавливает текущий путь, добавляемый к операцияем get и set
    */
    public function setCurrentPath
    (
        array $APath = []
    )
    {
        $this -> CurrentPath = $APath;
        return $this;
    }
}
