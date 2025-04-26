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

/*
    Валидатор.

    Разработан за период с 2022.10.15 по 2022.10.16 в рамках проекта Catlair для Pusa.
    Выполняет проверку Документа относительно Предоставленной схемы.

    Автор: still@itserv.ru
    Форк: https://gitlab.com/catlair/pusa/-/blob/main/php/core/checkup.php
*/
namespace catlair;



require_once 'result.php';
require_once 'utils.php';



class TCheckup extends Result
{
    /*
        Констаныты диркетив валидации
    */
    /* проверка существования значения */
    const DIR_EXISTS    = 'EXISTS';
    /* проверка тип значения */
    const DIR_TYPE      = 'TYPE';
    /* проверка пустого значения */
    const DIR_EMPTY     = 'EMPTY';
    /* проверка значения */
    const DIR_СMP       = 'CMP';
    /* проверка значения на регулярное выражение */
    const DIR_REG       = 'VAL_REG';
    /* проверка на вхождение в список */
    const DIR_IN        = 'IN';
    /* проверка длинны*/
    const DIR_LEN       = 'LEN';

    /*
        Константы типов переменных. Возвращаются методом getType
    */
    const TYPE_UNKNOWN  = 'unknown';    /* тип неизвестен */
    const TYPE_NULL     = 'null';       /* значение null */
    const TYPE_BOOL     = 'bool';       /* логический тип */
    const TYPE_INT      = 'int';        /* целочисленный тип */
    const TYPE_FLOAT    = 'float';      /* тип с плавающей точкой */
    const TYPE_STRING   = 'string';     /* тип строковый */
    const TYPE_ARRAY    = 'array';      /* массив */
    const TYPE_OBJECT   = 'object';     /* тип объекта */
    const TYPE_UUID     = 'uuid';       /* тип uuid */
    const TYPE_MOMENT   = 'moment';     /* тип момента времени в ODBC */

    /*
        Приватные свойства
    */
    private $Scheme     = [];   /* Объект средство валидации */
    private $Validation = [];   /* Объект результат валидации */
    private $Document   = null; /* Валидируемый документ */


    /*
        Создание валидатора
    */
    public static function create()
    {
        return new TCheckup();
    }



    /*
        Установка схемы валидации
    */
    public function setScheme
    (
        array $AValue = []
    )
    {
        $this -> Scheme = $AValue;
        return $this;
    }



    /*
        Получение схемы валидации
    */
    public function getScheme()
    {
        return $this -> Scheme;
    }



    /*
        Валидация документа
    */
    public function checkup
    (
        array $ADocument = []
    )
    {
        if( $this -> isOk() )
        {
            $this
            -> clearValidation()
            -> setDocument( $ADocument )
            -> processDirectives( $this -> getDirectives(), $this -> getDocument() );
        }
        return $this;
    }



    public function setDocument( $AValue )
    {
        $this -> Document = $AValue;
        return $this;
    }



    public function getDocument()
    {
        return $this -> Document;
    }


    /*
        Обработка директив
    */
    private function processDirectives
    (
        array $ADirectives = [],    /* Неименованный массив валидирующих директив */
        array $ADocument = []       /* Валидируемый документ в виде именованного массива */
    )
    {
        foreach( $ADirectives as $Directive )
        {
            $this -> processDirective( $Directive, $ADocument );
        }
        return $this;
    }



    /*
        Обработка директивы
    */
    private function processDirective
    (
        $ADirective,    /* Валидирующая директива */
        $ADocument      /* Валидируемый документ */
    )
    {
        if( $this -> isOk() )
        {
            /* Получение общих аргументов директивы */
            $Directive  = clValueFromObject( $ADirective, 'directive', null );
            $Path       = clValueFromObject( $ADirective, 'path', [] );

            if
            (
                $this
                -> ifResult( empty( $Path ), 'path_not_found' )
                -> ifResult( empty( $Directive ), 'directive_not_found' )
                -> isOk()
            )
            {
                /* Чтение значения из документа */
                $DocValue   = clValueFromObject( $ADocument, $Path );
                $DocType    = self::getType( $DocValue );

                /* Определение переменных для валидации */
                $Validation = false;    /* Результат валидации */

                /* Детали результата валидации */
                $Details =
                [
                    'value' => $DocValue,
                    'type' => $DocType
                ];

                /* Обработка директив валидации */
                switch( $Directive )
                {
                    case self::DIR_EXISTS:
                        $Validation = clValueExists( $ADocument, $Path );
                    break;

                    case self::DIR_EMPTY:
                        $Validation = empty( $DocValue );
                    break;

                    case self::DIR_TYPE:
                        $Validation = self::Compare
                        (
                            $DocType,
                            clValueFromObject( $ADirective, 'type', 'unknown' ),
                            clValueFromObject( $ADirective, 'operator', '=' )
                        );
                    break;

                    case self::DIR_СMP:
                        $Validation = self::Compare
                        (
                            $DocValue,
                            clValueFromObject( $ADirective, 'value', null ),
                            clValueFromObject( $ADirective, 'operator', '=' )
                        );
                    break;

                    case self::DIR_IN:
                        $Validation = in_array( $DocValue, clValueFromObject( $ADirective, 'list', [] ) );
                    break;

                    case self::DIR_LEN:
                        /* Получение длинны элемента */
                        switch( $DocType )
                        {
                            case 'string':
                                $Length = mb_strlen( $DocValue );
                            break;
                            case 'array':
                            case 'object':
                                $Length = count( $DocValue );
                            break;
                            default:
                                $Length = 1;
                            break;
                        }

                        /* Сохранение в деталях длинны */
                        $Details[ 'Length' ] = $Length;

                        /* Валидация */
                        $Validation = self::Compare
                        (
                            $Length,
                            clValueFromObject( $ADirective, 'length', null ),
                            clValueFromObject( $ADirective, 'operator', '=' )
                        );
                    break;

                    case self::DIR_REG:
                        switch( $DocType )
                        {
                            case 'string':
                                $Regular    = clValueFromObject( $ADirective, 'regular', '' );
                                $Validation = preg_match( $Regular, $DocValue );
                            break;
                            default:
                                $Validation = false;
                            break;
                        }
                    break;

                    default:
                        $this -> setResult( 'unknown_directive', [ 'Directive' => $ADirective ]);
                    break;
                }

                /* Проверка Directives */
                $Directives = clValueFromObject( $ADirective, 'directives', [] );
                if( empty( $Directives ))
                {
                    /* Сохранение результатов отрицательной валидации */
                    $this -> writeValidation
                    (
                        $Validation
                        ? clValueFromObject( $ADirective, 'true', Result::RC_OK )
                        : clValueFromObject( $ADirective, 'false', Result::RC_OK ),
                        $ADirective,
                        $Details
                    );
                }
                else
                {
                    if( $Validation )
                    {
                        /* Запуск валидации поддиректив */
                        $this -> processDirectives( $Directives, $ADocument );
                    }
                }
            }
        }

        return $this;
    }



    /*
        Сохранение резульатов валидации
    */
    private function writeValidation
    (
        string  $ACode      = '',
        array   $ADirective = [],
        array   $ADetails   = []
    )
    {
        if( $ACode != Result::RC_OK )
        {
            array_push
            (
                $this -> Validation,
                [
                    'code'      => $ACode,
                    'directive' => $ADirective,
                    'document'  => $ADetails
                ]
            );
        }
        return $this;
    }



    /*
        Очизаеься результат валидации
    */
    public function clearValidation()
    {
        $this -> Validation = [];
        return $this;
    }



    /*
        Возвращается результат валидации
    */
    public function getValidation()
    {
        return $this -> Validation;
    }



    /*
        Сравнение двух операндов посредством оператора
    */
    private static function Compare
    (
        $AOperand1 = null,  /* Оператор 1 */
        $AOperand2 = null,  /* Оператор 2 */
        $AOperator = []     /* Операнд */
    )
    {
        switch( $AOperator )
        {
            case '='    : $Result = $AOperand1 == $AOperand2;  break;
            case '!='   : $Result = $AOperand1 != $AOperand2;  break;
            case '>'    : $Result = $AOperand1 >  $AOperand2;  break;
            case '<'    : $Result = $AOperand1 <  $AOperand2;  break;
            case '>='   : $Result = $AOperand1 >= $AOperand2;  break;
            case '<='   : $Result = $AOperand1 <= $AOperand2;  break;
            default     : $Result = false;
        }
        return $Result;
    }



    /*
        Получение типа значения.
        Возвращает тип значения в виде строки.
    */
    private static function getType
    (
        $AValue /* Значение любого типа */
    )
    {
        switch( gettype( $AValue ))
        {
            case 'NULL'     : $Result = self::TYPE_NULL; break;
            case 'boolean'  : $Result = self::TYPE_BOOL; break;
            case 'integer'  : $Result = self::TYPE_INT; break;
            case 'double'   : $Result = self::TYPE_FLOAT; break;
            case 'array'    : $Result = self::TYPE_ARRAY; break;
            case 'object'   : $Result = self::TYPE_OBJECT; break;
            case 'string'   :
                if( clIsGUID( $AValue ))
                {
                    $Result = self::TYPE_UUID;
                }
                else
                {
                    $Result = self::TYPE_STRING;
                }
            break;
            default:
                $Result = self::TYPE_UNKNOWN;
            break;
        }
        return $Result;
    }



    /*
        Возвращается мажорная версия схемы валидации
    */
    public function getVersionMajor()
    {
        return clValueFromObject( $this -> Scheme, 'major', 'alpha' );
    }



    /*
        Возвращается минорная версия схемы валидации
    */
    public function getVersionMinor()
    {
        return clValueFromObject( $this -> Scheme, 'minor', 0 );
    }



    /*
        Возвращается мажорная версия схемы валидации
    */
    public function getDirectives()
    {
        return clValueFromObject( $this -> Scheme, 'directives', [] );
    }



    /* Возвращается версия в нотации мажорная.минорная */
    public function getVersion()
    {
        return $this -> GetVersionMajor() . '.' . $this -> GetVersionMinor();
    }
}
