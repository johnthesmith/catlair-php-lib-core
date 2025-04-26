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
    Базовый класс хранения результата (состояния) системы
    Имползуется как основа классов.
*/



class Result
{
    /* Default result code */
    const RC_OK = 'ok';



    /*
        Приватные свойства состояния
    */
    private $Code        = self::RC_OK;      /* Умолчальное значение результата */
    private $Message     = '';               /* Пустое значение сообщения */
    private $Detaile     = [];               /* Накопительный именованный массив детализация текущего состояния в формате ключ=>значение */
    private $InfoFlag    = false;            /* Текущая ошибка является информацонной. Содержится в объекте но при всех проверках вернет RC_OK */



    /*
        Создание результата из массива
    */
    static public function fromArray
    (
        array $AArray = []
    )
    {
        $result = new Result();
        $key = clValueFromObject( $AArray, 'result', clValueFromObject( $AArray, 'Result' ));

        $result -> setResult
        (
            (string) clValueFromObject( $key, 'code', clValueFromObject( $key, 'Code', 'UnknownError' )),
            (array) clValueFromObject( $key, 'detailes', clValueFromObject( $key, 'Detailes' )),
            (string)  clValueFromObject( $key, 'message', clValueFromObject( $key, 'Message' ))
        );
        return $result;
    }



    /*
        return true if result is Ok
    */
    public function isOk()
    {
        return $this -> Code == self::RC_OK;
    }



    /*
        Method return true for Code = ACode
    */
    public function isCode
    (
        $ACode
    )
    {
        return $this -> getCode() == $ACode;
    }



    /*
        Устанавливает состояние результата в положение отсутсвия ошибок
    */
    public function setOk
    (
        /*
            В случае, если текущая ошибка содержится в списке или список пуст, будет установлен результат ОК
        */
        array $AListCodeCheck = []
    )
    {
        if( count( $AListCodeCheck ) == 0 || in_array( $this -> Code, $AListCodeCheck ))
        {
            $this -> InfoFlag = false;
            $this -> Code = self::RC_OK;
        }
        return $this;
    }



    /*
        Устанавливает код ошибки
    */
    public function setCode
    (
        string  $AValue,     /* Значение кода ошибки */
        bool    $AInfoFlag = false  /* При true код ошибки будет восприниматься как OK при последующих операциях проверки */
    )
    {
        $this -> InfoFlag   = $AInfoFlag;
        $this -> Code       = $AValue;
        return $this;
    }



    /*
        Устанавливается параметр детализации ошибки
    */
    public function setDetaile
    (
        string $AName,  /* Имя параметра детализации состояния */
        $AValue = null  /* Значение параметра детализации состояния */
    )
    {
        $this -> Detaile[ $AName ] = $AValue;
        return $this;
    }


    /*
        Возвращает значение ключа детализации
    */
    public function getDetaile
    (
        string $AName,
        $ADefault = null
    )
    {
        if ( array_key_exists( $AName, $this -> Detaile ))
        {
            $Result = $this -> Detaile[ $AName ];
        }
        else
        {
            $Result = $ADefault;
        }
        return $Result;
    }



    public function getDetailes()
    {
        return $Result = $this -> Detaile;
    }



    /*
        Get result code
    */
    public function getCode()
    {
        return $this -> Code;
    }



    /*
        Build message from replaceing parameters
    */
    public function buildMessage
    (
        $AReplace = [],         /* */
        string $APrefix = '',
        string $ASuffix = ''
    )
    {
        $this -> SetMessage( $this -> getMessage( $AReplace, $APrefix, $ASuffix ));
    }



    /*
        Set result message
    */
    public function setMessage
    (
        $AValue
    )
    {
        $this -> Message = $AValue;
        return $this;
    }



    /*
        Получение сообщения
    */
    public function getMessage
    (
        array $AReplace = [], /* Named array for replace */
        string $APrefix = '',
        string $ASuffix = ''
    )
    {
        $Result = $this -> Message;
        if( empty( $Result )) $Message = $this -> Code;

        $Keys = [];
        $Values = [];

        /* Replace for datailes */
        foreach( $this -> Detaile as $Key => $Value )
        {
            array_push( $Keys, '%' . $Key . '%' );
//            array_push( $Values, $APrefix . ( gettype( $Value == 'string' ) ? $Value : '' ) . $ASuffix );
        }

        /* Replace for argument */
        foreach( $AReplace as $Key => $Value )
        {
            array_push( $Keys, $Key );
            array_push( $Values, $Value );
        }

        $Result = str_replace( $Keys, $Values, $Result );

        return $Result;
    }



    /*
        Установка результата
    */
    public function setResult
    (
        string $ACode      = self::RC_OK,   /* Код ошибки */
        array $ADetaile    = [],            /* Массив детализации ошибок, который будет объединен с предыдущим */
        string $AMessage   = null           /* Сообщение ошибки */
    )
    {
        $this -> setCode( $ACode );
        $this -> setMessage( $AMessage );
        $this -> Detaile = $ADetaile;
        return $this;
    }



    /*
        Установка результата из массива
    */
    public function setResultFromArray
    (
        array $AArray = []
    )
    {
        $result = clValueFromObject( $AArray, 'result', clValueFromObject( $AArray, 'Result' ));
        $this -> setResult
        (
            (string) clValueFromObject( $result, 'code', clValueFromObject( $result, 'Code', 'UnknownError' )),
            (array) clValueFromObject( $result, 'detailes', clValueFromObject( $result, 'Detailes' )),
            (string)  clValueFromObject( $result, 'message', clValueFromObject( $result, 'Message' ))
        );
        return $this;
    }



    /*
        Установка результата из массива
    */
    public function getResultAsArray()
    {
        return
        [
            'code'      => $this -> getCode(),
            'detailes'  => $this -> getDetailes(),
            'message'   => $this -> getMessage()
        ];
    }



    /*
        Валидация результата
    */
    public function ifResult
    (
        bool    $ANotValidate   = false,        /* Условие валидации, при истине ошибка будет установлена */
        string  $ACode          = self::RC_OK,  /* Код ошибки */
        array   $ADetaile       = [],           /* Массив детализации ошибок, который будет объединен с предыдущим */
        string  $AMessage       = null          /* Сообщение ошибки */
    )
    {
        return $this -> validate
        (
            $ANotValidate,
            $ACode,
            $ADetaile,
            $AMessage
        );
    }



    /*
        Валидация результата
    */
    public function validate
    (
        bool    $ANotValidate   = false,        /* Условие валидации, при истине ошибка будет установлена */
        string  $ACode          = self::RC_OK,  /* Код ошибки */
        array   $ADetaile       = [],           /* Массив детализации ошибок, который будет объединен с предыдущим */
        string  $AMessage       = null          /* Сообщение ошибки */
    )
    {
        if( $this -> isOk() && $ANotValidate )
        {
            $this -> setResult( $ACode, $ADetaile, $AMessage );
        }
        return $this;
    }



    /*
        Переносит результат из источника в текущий объект
    */
    public function resultFrom
    (
        Result &$ASource,       /* Объект источник для получения состояния результата */
        string $APrefix = null  /* Префикс ошибки, который будет добавлен при переносе */
    )
    {
        if ( $ASource -> InfoFlag )
        {
            $this -> SetOk();
            $this -> Message    = '';
        }
        else
        {
            $this -> Code =
            ( empty( $APrefix ) ? '' : $APrefix ) . $ASource -> Code;

            $this -> Message = $ASource -> Message;

            $this -> Detaile =
            array_merge( $this -> Detaile, $ASource -> Detaile );
        }
        return $this;
    }



    /*
        Переносит результат текущего объекта в объект направления
    */
    public function resultTo
    (
        Result &$ATarget,       /* Объект направления для переноса результата из текущего объекта */
        string $APrefix = null  /* Префикс ошибки, который будет добавлен при переносе */
    )
    {
        if( $this -> InfoFlag )
        {
            $ATarget -> SetOk();
            $ATarget -> Message = '';
        }
        else
        {
            $ATarget -> Code =
            ( empty( $APrefix ) ? '' : $APrefix ) . $this -> Code;

            $ATarget -> Message =
            $this -> Message;

            $ATarget -> Detaile =
            array_merge( $ATarget -> Detaile, $this -> Detaile );
        }
        return $this;
    }
}


