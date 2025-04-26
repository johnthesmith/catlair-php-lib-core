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



/* Локальные библиотек */
require_once 'utils.php';
require_once 'result.php';



class Csv extends Result
{
    /* Внутренний список файлов */
    private $files              = [];
    /* Максимальный размер исходящего файла */
    private $maxOuteByte        = 1024 * 1024 * 1024;
    /* Строка квотирования для ячеек заголова */
    private $headerCellQuote    = '"';
    /* Строка квотирования для ячеек записей */
    private $recordCellQuote    = '"';


    /*
        Create CSV object
    */
    static function Create()
    {
        $Result = new self();
        return $Result;
    }



    /*
        Build csv files from array
    */
    public function fromArray
    (
        array $a    /* Array of records with key => value format */
    )
    {
        $files = [];

        if( !empty( $a ))
        {
            /* Сборка заголовка */
            $head =
            implode
            (
                ';',
                array_map
                (
                    function( $s )
                    {
                        return
                        $this -> headerCellQuote
                        . $s
                        . $this -> headerCellQuote;
                    },
                    array_keys( (array) $a[ 0 ] )
                )
            );

            /* Сборка строк в массив records */
            $records = [];
            /* Цикл по всем строкам массива аргумента с вызовом callback */
            array_map
            (
                function( $item ) use ( &$records )
                {
                    if( !empty( $item ))
                    {
                        array_push
                        (
                            $records,
                            implode
                            (
                                ';',
                                array_map
                                (
                                    function( $s )
                                    {
                                        return
                                        $this -> recordCellQuote
                                        . $s
                                        . $this -> recordCellQuote;
                                    },
                                    (array) $item
                                )
                            )
                        );
                    }
                    return $item;
                },
                (array) $a
            );

            /* Сборка файлов */
            $sizeOfHead = strlen( $head );
            $sizeOfFile = $sizeOfHead;
            $i = 0;
            $c = count( $records );

            while( $this -> isOk() && $i < $c )
            {
                $record = $records[ $i ];
                $sizeOfRecord = strlen( $record );

                if( $sizeOfHead + $sizeOfRecord > $this -> maxOuteByte )
                {
                    /* Size of record plus head more limits */
                    $this -> setCode( 'SumOfSizeHeadPlusRecordMoreFileLimit' );
                }
                else
                {
                    if
                    (
                        /* for limit size or ...*/
                        $sizeOfFile + $sizeOfRecord > $this -> maxOuteByte ||
                        /* files is empty */
                        empty( $this -> files )
                    )
                    {
                        /* Create new file */
                        $this -> newFile( $head );
                        $sizeOfFile = $sizeOfHead;
                    }

                    /* Add record to file */
                    $this -> addRecordToFile( $record );
                    $sizeOfFile += $sizeOfRecord;
                }
                $i++;
            }
        }

        return $this;
    }



    /*
        Define a new file array
    */
    private function newFile
    (
        string $aHead
    )
    {
        $file = [];
        array_push( $file, $aHead );
        array_push( $this -> files, $file );
        return $this;
    }



    /*
        Add a new record to curren file array
    */
    private function addRecordToFile
    (
        string $aRecord
    )
    {
        array_push( $this -> files[ count( $this -> files ) - 1 ], $aRecord );
        return $this;
    }



    /*
        Return array of files
    */
    public function getFiles()
    {
        return $this -> files;
    }



    /*
        Simple loop for files list
    */
    public function loop
    (
        $aFunc
        /*
            Calback function for each record in file
                $aSelf - the curretn Csv object
                $aCurrent - index of current file
                $aTotal - total coute of file
                $aContent - content of the current file
        */
    )
    {
        if( $this -> isOk() )
        {
            $c = count( $this -> files );
            for( $i = 0; $i < $c; $i++ )
            {
                $file = $this -> files[ $i ];
                $aFunc( $this, $i, $c, implode( PHP_EOL, $file ));
            }
        }
        return $this;
    }



    /*
        Set the maximum size of out file
    */
    public function setMaxOutByte
    (
        int $aValue
    )
    {
        $this -> maxOuteByte = $aValue;
        return $this;
    }




    /*
        Set the maximum size of out file
    */
    public function getMaxOutByte()
    {
        return $this -> maxOuteByte;
    }



    /*
        Установка значения квотирования ячеек заголовков
    */
    public function setHeaderCellQuote
    (
        string $aValue
    )
    {
        $this -> headerCellQuote = $aValue;
        return $this;
    }



    /*
        Установка значения квотирования ячеек строк
    */
    public function setRecordCellQuote
    (
        string $aValue
    )
    {
        $this -> recordCellQuote = $aValue;
        return $this;
    }
}
