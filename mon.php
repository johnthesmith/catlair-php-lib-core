<?php
namespace catlair;
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
    Модуль мониторинга

    Модуль построен по принципу накопительного автомата.
    В процессе исполнения собираются управляющие директивы и аргументы.
    При сбросе информации происходит последовательное исполненеие
    директив и выгрузка результатов.
*/



require_once 'params.php';
require_once 'log.php';
require_once 'moment.php';



class Mon extends Params
{
    /*
        Именованные директивы
    */
    const CMD_SET       = 'set';
    const CMD_GET       = 'get';
    const CMD_MOVE      = 'move';
    const CMD_ADD       = 'add';
    const CMD_SUM       = 'sum';
    const CMD_STOP      = 'stop';
    const CMD_DELTA_NOW = 'delta_now';
    const CMD_AVG       = 'avg';
    const CMD_MIN       = 'min';
    const CMD_MAX       = 'max';
    const CMD_SORT      = 'sort';
    const CMD_SIZE      = 'size';   /* Расчет размера в байтах или иных единицах */
    const CMD_CUT       = 'cut';    /* Команда обрезания массива до указанной длинны */

    const SORT_BY_VALUE = 'val';    /* Тип сортировки по значениям */
    const SORT_BY_KEY   = 'key';    /* Тип сортировки по ключу */
    const SORT_ORDER_ZA = 'za';     /* Тип сортировки za */
    const SORT_ORDER_AZ = 'az';     /* Тип сортировки az */

    /* Приватные состояния */
    private $Log            = null; /* Объект логирования */
    private $filePath       = '';   /* Файлоый путь для вывода */
    private $fileName       = '';   /* Файловое имя для вывода */
    private $cmds           = [];   /* Перечень директив */

    /*
        Конструктор мониторинга
    */
    function __construct
    (
        Log $ALog   /* Объект логирования */
    )
    {
        $this -> Log = $ALog;
    }



    /*
        Создается и возвращается объект мониторинга
    */
    static public function create
    (
        Log $ALog   /* Объект логирования */
    )
    {
        return new Mon( $ALog );
    }



    /**************************************************************************
        Пользовательские директивы
    */

    /*
        Установка значения
    */
    public function set
    (
        $AName,         /* Имя или путь ключа */
        $AValue,        /* Устанавливаемое значение */
        $AOnce = false  /* Устанавить только один раз */
    )
    {
        return
        $this -> addCmd
        (
            self::CMD_SET,
            [
                'Name'  => $AName,
                'Value' => $AValue,
                'Once'  => $AOnce
            ]
        );
    }



    /*
        Возврат значения
    */
    public function get
    (
        $AName,             /* Имя ключая или путь ключа */
        $ADefault = null    /* Умолчальное значение */
    )
    {
        return
        $this -> addCmd
        (
            self::CMD_GET,
            [
                'Name' => $AName,
                'Default' => $ADefault
            ]
        );
    }



    /*
        Установка значения
    */
    public function move
    (
        $AFrom,             /* Имя ключа источника */
        $ATo,               /* Имя ключа направления */
        $ADefault = null    /* Умолчальное значение */
    )
    {
        return
        $this -> addCmd
        (
            self::CMD_MOVE,
            [
                'From'      => $AFrom,
                'To'        => $ATo,
                'Default'   => $ADefault
            ]
        );
    }



    /*
        Инкремент значения
    */
    public function add
    (
        $AName,
        $AValue = 1
    )
    {
        return
        $this -> addCmd
        (
            self::CMD_ADD,
            [
                'Name'  => $AName,
                'Value' => $AValue
            ]
        );
    }



    /*
        Инкремент значения
    */
    public function sum
    (
        $AName,
        $AFrom = 1
    )
    {
        return
        $this -> addCmd
        (
            self::CMD_SUM,
            [
                'Name'  => $AName,
                'From'  => $AFrom
            ]
        );
    }



    /*
        Установка момента времени для ключа
    */
    public function now
    (
        $AName,
        $AOnce = false
    )
    {
        return $this
        -> set
        (
            $AName,
            Moment::create() -> now() -> toString( Moment::ODBC_MLS_FORMAT ),
            $AOnce
        );
    }



    /*
        Установка момента начала операции для ключа Name
        В последствии ключ должен быть завершен отправкой
        метода stop для того же ключа.
    */
    public function start
    (
        $AName
    )
    {
        return
        $this -> set( $AName, Moment::create() -> now() -> get());
    }



    /*
        Установка завершения операции с расчетом времени исполнения
        для ключа Name
    */
    public function stop
    (
        $AName,
        $ATimeScale = 1
    )
    {
        return
        $this -> addCmd
        (
            self::CMD_STOP,
            [
                'Name'      => $AName,
                'Value'     => Moment::create() -> now() -> get(),
                'TimeScale' => $ATimeScale
            ]
        );
    }



    /*
        Расчет интервала времени между значением базовым и текущим моментом
    */
    public function deltaNow
    (
        $ABase, /* имя атрибута содержащего момент времени в формате ODBC*/
        $ATo,   /* имя атрибута для размещения значения */
    )
    {
        return
        $this -> addCmd
        (
            self::CMD_DELTA_NOW,
            [
                'Base'      => $ABase,
                'Now'       => Moment::create() -> now() -> get(),
                'To'        => $ATo
            ]
        );
    }



    /*
        Расчет среднего значения
    */
    public function avg
    (
        $AName,     /* Направление для сохранения значений */
        $AOperand1, /* Операнд делимое */
        $AOperand2  /* Операнд делитель */
    )
    {
        return
        $this -> addCmd
        (
            self::CMD_AVG,
            [
                'Name'      => $AName,
                'Operand1'  => $AOperand1,
                'Operand2'  => $AOperand2
            ]
        );
    }



    /*
        Расчет минимального зщначения
    */
    public function min
    (
        $AName,     /* Направление для сохранения значений */
        $AOperand   /* Операнд проверки минимума с сохраняемым значением */
    )
    {
        return
        $this -> addCmd
        (
            self::CMD_MIN,
            [
                'Name'      => $AName,
                'Operand'  => $AOperand
            ]
        );
    }



    /*
        Расчет максимального зщначения
    */
    public function max
    (
        $AName,     /* Направление для сохранения значений */
        $AOperand   /* Операнд проверки максимума с сохраняемым значением */
    )
    {
        return
        $this -> addCmd
        (
            self::CMD_MAX,
            [
                'Name'     => $AName,
                'Operand'  => $AOperand
            ]
        );
    }



    /*
        Dump trace information from log object to mon object
    */
    public function dumpTraceLog
    (
        Log $ALog = null
    )
    {
        $ALog = empty( $ALog ) ? $this -> getLog() : $ALog;
        if( !empty( $ALog ))
        {
            $Trace = $ALog -> getTraceResult();

            /* Trace information sorting */
            uasort
            (
                $Trace,
                function($a, $b)
                {
                    if( $a[ 'Delta' ] > $b[ 'Delta' ]) return -1;
                    elseif( $a[ 'Delta' ] < $b[ 'Delta' ]) return 1;
                    else return 0;
                }
            );

            foreach( $Trace as $Key => $Value)
            {
                $this
                -> set( [ 'trace', $Key, 'time_ms' ], number_format( $Value['Delta'] * 1000, 2, '.', ' ' ))
                -> set( [ 'trace', $Key, 'count' ], $Value[ 'Count' ]);
            }
        }

        return $this;
    }



    /*
        Расчет минимального зщначения
    */
    public function sort
    (
        $AName,                         /* Направление для сохранения значений */
        $AType  = self::SORT_BY_VALUE,  /* Тип сортирвки */
        $AOrder = self::SORT_ORDER_AZ   /* Порядок сортировки */
    )
    {
        return
        $this -> addCmd
        (
            self::CMD_SORT,
            [
                'Name'      => $AName,
                'Type'      => $AType,
                'Order'     => $AOrder
            ]
        );
    }



    /*
        Обрезаем массив в ключе до указанной длинны
        Если массив содержит более указанной длинны, он будет урезан
        В противном случае массив не будет изменен
    */
    public function cut
    (
        $AName, /* Обрезаемый массив */
        $ALimit /* Длинна массива */
    )
    {
        return
        $this -> addCmd
        (
            self::CMD_SORT,
            [
                'Name'      => $AName,
                'Limit'     => $ALimit
            ]
        );
    }



    /*
        Расчет размера в байтах
    */
    public function size
    (
        $AFrom, /* Ключ источник */
        $ATo    /* Ключ направление */
    )
    {
        return
        $this -> addCmd
        (
            self::CMD_SIZE,
            [
                'From'  => $AFrom,
                'To'    => $ATo
            ]
        );
    }



    /**************************************************************************
        Инструментальные методы
    */



    public function read
    (
        string $AFile = null
    )
    {
        $result = [];
        $AFile = empty( $AFile ) ? $this -> getFilePathName() : $AFile;
        if( !empty( $AFile ))
        {
            /* Читаем файл */
            $size = filesize( $AFile );
            $content = $size > 0 ?  file_get_contents( $AFile ) : '';
            $json = json_decode( trim( $content ), true );
            $result = empty( $json ) ? [] : $json;
        }
        return $result;
    }



    /*
        Выгрузка мониторинга в файл
    */
    public function flush
    (
        string $AFile = null
    )
    {
        $AFile = empty( $AFile ) ? $this -> getFilePathName() : $AFile;
        if( !empty( $AFile ))
        {
            if( clCheckPath( dirname( $AFile )))
            {
                $handle = @fopen( $AFile, 'c+' );
                if( $handle !== false )
                {
                    fseek( $handle, 0 );
                    /* Попытка блокировки файла для экслюзивного доступа */
                    if( flock( $handle, LOCK_EX ))
                    {
                        /* Читаем файл */
                        $json = $this -> read( $AFile );
                        $this -> setParams( $json );

                        /* Транкируем файл */
                        fseek( $handle, 0 );
                        ftruncate( $handle, 0 );

                        /* Запускаем исполнение директив над загруженным контентом */
                        $this -> runCmds();

                        $content =
                        json_encode
                        (
                            $this -> getParams(),
                            JSON_PRETTY_PRINT |
                            JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES
                        );

                        $forWrite = strlen( $content );

                        /* Записываем файл */
                        $write = fwrite( $handle, $content, $forWrite  );

                        if( $forWrite != $write )
                        {
                            $this -> getLog() -> warning( 'ErrorWriteBuffer' );
                        }

                        /* Очистка директив после исполнения */
                        $this -> cmds = [];

                        /* Разблокируем файл */
                        flock( $handle, LOCK_UN );
                    }
                    fclose( $handle );
                }
                else
                {
                    $this -> setResult( 'MonitorFileOpenError' );
                }
            }
            else
            {
                $this -> setResult( 'MonitorFilePathError' );
            }
        }

        return $this;
    }


    /*
        Добавление директивы
    */
    private function addCmd
    (
        $aCmd,
        $aArgs
    )
    {
        array_push( $this -> cmds, [ 'name' => $aCmd, 'args' => $aArgs ]);
        return $this;
    }



    /*
        Исполнение директив
    */
    private function runCmds()
    {
        foreach( $this -> cmds as $cmd )
        {
            /* Получение аргументов */
            $args = $cmd[ 'args' ];

            /* Обработка коменды */
            switch( $cmd[ 'name' ])
            {
                case self::CMD_SET:
                    if( empty( $this -> getParam( $args[ 'Name' ] )) || !$args[ 'Once' ])
                    {
                        $this -> setParam( $args[ 'Name' ], $args[ 'Value' ]);
                    }
                break;
                case self::CMD_GET:
                    $this -> getParam( $args[ 'Name' ], $args[ 'Default' ]);
                break;
                case self::CMD_MOVE:
                    $this -> setParam
                    (
                        $args[ 'From' ],
                        $this -> getParam( $args[ 'To' ], $args[ 'Default' ])
                    );
                break;
                case self::CMD_ADD:
                    $this -> setParam
                    (
                        $args[ 'Name' ],
                        (float) $this -> getParam( $args[ 'Name' ], 0 ) +
                        (float) $args[ 'Value' ]
                    );
                break;
                case self::CMD_SUM:
                    $this -> setParam
                    (
                        $args[ 'Name' ],
                        (float) $this -> getParam( $args[ 'Name' ], 0 ) +
                        (float) $this -> getParam( $args[ 'From' ], 0 )
                    );
                break;
                case self::CMD_STOP:
                    $this -> setParam
                    (
                        $args[ 'Name' ],
                        (
                            $args[ 'Value' ] - $this -> getParam( $args[ 'Name' ], 0 )
                        ) / $args[ 'TimeScale' ]
                    );
                break;
                case self::CMD_DELTA_NOW:
                    $last = Moment::create() -> fromText( $this -> getParam( $args[ 'Base' ])) -> get();
                    $this -> setParam
                    (
                        $args[ 'To' ],
                        clMomentDeltaToString
                        (
                            $args[ 'Now' ] - ( empty( $last ) ? $args[ 'Now' ] : $last ),
                            null,
                            DELTA_NAME
                        )
                    );
                break;
                case self::CMD_AVG:
                    $a2 = (float) $this -> getParam( $args[ 'Operand2' ]);
                    $this
                    -> setParam
                    (
                        $args[ 'Name' ],
                        $a2 < 1e-10
                        ? 0
                        : ( $this -> getParam( $args[ 'Operand1' ]) / $a2 )
                    );
                break;
                case self::CMD_MIN:

                    $this -> setParam
                    (
                        $args[ 'Name' ],
                        min
                        (
                            $this -> getParam( $args[ 'Name' ], INF ),
                            $this -> getParam( $args[ 'Operand' ], INF )
                        )
                    );
                break;
                case self::CMD_MAX:
                    $this -> setParam
                    (
                        $args[ 'Name' ],
                        max
                        (
                            $this -> getParam( $args[ 'Name' ], -INF ),
                            $this -> getParam( $args[ 'Operand' ], -INF )
                        )
                    );
                break;
                case self::CMD_SORT:
                    $items = $this -> getParam( $args[ 'Name' ], [] );
                    $order = $args[ 'Order' ] == self::SORT_ORDER_AZ ? 1 : -1;
                    switch( $args[ 'Type' ] )
                    {
                        case self::SORT_BY_KEY:
                            uksort
                            (
                                $items,
                                function ( $a, $b ) use ( $order )
                                {
                                    if( $a == $b )
                                    {
                                        return 0;
                                    }
                                    return $a > $b ? $order : -$order;
                                }
                            );
                        break;
                        case self::SORT_BY_VALUE:
                            uasort
                            (
                                $items,
                                function ( $a, $b ) use ( $order )
                                {
                                    if( $a == $b )
                                    {
                                        return 0;
                                    }
                                    return $a > $b ? $order : -$order;
                                }
                            );
                        break;
                    }
                    $this -> setParam( $args[ 'Name' ], $items );
                break;
                case self::CMD_CUT:
                    $this -> setParam
                    (
                        $args[ 'Name' ],
                        array_slice
                        (
                            $this -> getParam( $args[ 'Name' ], [] ),
                            0,
                            $args[ 'Limit' ]
                        )
                    );
                break;
                case self::CMD_SIZE:
                    $this -> setParam
                    (
                        $args[ 'To' ],
                        clSizeToStr( $this -> getParam( $args[ 'From' ], 0 ))
                    );
                break;
            }
        }

        return $this;
    }



    /**************************************************************************
        Сеттеры и геттеры
    */

    /*
        Return the Log object
    */
    public function getLog()
    {
        return $this -> Log;
    }



    /*
        Set file path and name
    */
    public function setFilePathName
    (
        string $AValue = null
    )
    {
        return $this
        -> setFilePath( dirname( (string) $AValue ))
        -> setFileName( basename( (string) $AValue ));
    }



    /*
        Set file path and name
    */
    public function getFilePathName()
    {
        return
        (
            (
                empty( $this -> filePath )
                ? ''
                : $this -> filePath . '/'
            ) . $this -> fileName
        );
    }



    /*
        Set file path
    */
    public function setFilePath
    (
        string $AValue
    )
    {
        $this -> filePath = $AValue;
        return $this;
    }



    /*
        Set file path
    */
    public function setFileName
    (
        string $AValue
    )
    {
        $this -> fileName = $AValue;
        return $this;
    }



    /*
        Удаляет файл статистики
    */
    public function drop
    (
        string $AFile = null
    )
    {
        $AFile = empty( $AFile ) ? $this -> getFilePathName() : $AFile;
        if( !empty( $AFile ) && file_exists( $AFile ))
        {
            unlink( $AFile );
        }
        return $this;
    }
}
