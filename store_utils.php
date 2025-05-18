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
    Модуль работы с файлам хранилиша
    still@itserv.ru
    2024.02.08
*/

namespace catlair;



require_once 'result.php';
require_once 'utils.php';


/*
    Устанавливает значение хранилища
    Попытка шифрование выполняется при наличии ключае шифрования
*/
function clWriteStore
(
    /* Путь файла хранилиза */
    $AFile,
    /* Устанавливаемое значение */
    $AValue,
    /* Ключ шифрования */
    $ASSLKey            = null,
    /* Метод шифрования openssl_get_cipher_methods() */
    $ASSLMethod         = 'aes-256-cbc',
    /* Длина Cтартового вектора */
    $ASSLVectorLength   = 16
)
:Result
{
    /* Result */
    $result = new Result();

    /* Проверка пути размещения файла */
    if( clCheckPath( dirname( $AFile )))
    {
        /* Шифрование при наличии имени метода */
        $InitVector = null;
        if( !empty( $ASSLKey ))
        {
            $Vector = openssl_random_pseudo_bytes( $ASSLVectorLength );

            /* Шифрование */
            $Value = openssl_encrypt
            (
                $AValue,
                $ASSLMethod,
                $ASSLKey,
                OPENSSL_RAW_DATA,
                $Vector
            );

            if( $Value === false )
            {
                $result -> setResult
                (
                    'StoreEncriptionError',
                    [
                        'method'        => $ASSLMethod,
                        'vector_length' => $ASSLLengthVector
                    ]
                );
            }
            else
            {
                $AValue = json_encode
                ([
                    'method' => $ASSLMethod,
                    'vector' => bin2hex( $Vector ),
                    'value'  => bin2hex( $Value )
                ]);
            }
        }

        /* Рзамещение в файле */
        $handle = fopen( $AFile, 'c+' );
        $successWrite = false;
        if( $handle )
        {
            if( flock( $handle, LOCK_EX ))
            {
                /* транкируем файл */
                $successWrite = ftruncate( $handle, 0 );
                /* запись */
                $successWrite = $successWrite && ( fwrite( $handle, $AValue ) !== false );
                /* снятие блокировки */
                flock( $handle, LOCK_UN );
            }
            fclose( $handle );
        }
        if( ! $successWrite )
        {
            $result -> setResult( 'StoreFileNotWrite', [ 'file' => $AFile ] );
        }
    }
    else
    {
        $result -> setResult( 'StorePathNotCreated', [ 'file' => $AFile ] );
    }

    return $result;
}



/*
    Возвращает значение хранилища
    При наличии ключа шифрования выполняется попытка дешифровки.
    Алгоритм шифрвоания и вектор получаются из файла
*/
function clReadStore
(
    &$AValue,           /* Возвращаемое значение */
    $AFile,             /* Имя ключа или путь в виде массива строк*/
    $ADefault   = null, /* Умолчальное значнеи при отсутсвии */
    $ASSLKey    = null  /* Ключ шифрования SSL */
)
:Result
{
    $result = new Result();
    $AValue = null;

    /* Чтение файла */
    if( file_exists( $AFile ))
    {
        $Handle = fopen( $AFile, 'r' );
        if( $Handle )
        {
            if( flock( $Handle, LOCK_SH ))
            {
                $size = filesize( $AFile );
                if( $size > 0 )
                {
                    $AValue = @fread( $Handle, $size );
                }
                flock( $Handle, LOCK_UN );
            }
            else
            {
                $result -> setResult( 'FileLockError', [ 'file' => $AFile ] );
                $AValue = $ADefault;
            }
            fclose( $Handle );
        }
        else
        {
            $result -> setResult( 'FileIsNotOpened', [ 'file' => $AFile ] );
            $AValue = $ADefault;
        }
    }
    else
    {
        $result -> setResult( 'FileNotExists', [ 'file' => $AFile ]);
        $AValue = $ADefault;
    }

    /* Дешифрование при наличии имени метода */
    if( $AValue !== null && !empty( $ASSLKey ))
    {
        $Json = json_decode( $AValue, true );
        if( !@empty( $Json ))
        {
            $AValue = openssl_decrypt
            (
                hex2bin( clValueFromObject( $Json, 'value' )),
                clValueFromObject( $Json, 'method' ),
                $ASSLKey,
                OPENSSL_RAW_DATA,
                hex2bin( clValueFromObject( $Json, 'vector' ))
            );
            if( $AValue === 'false' )
            {
                $result -> setResult
                (
                    'EncriptedError',
                    [
                        'file' => $AFile,
                        'code' => openssl_error_string()
                    ]
                );
                $AValue = $ADefault;
            }
        }
    }

    return $result;
}
