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
    Валидатор документа

    Разработан за период с 2022.10.15 по 2022.10.16 в рамках проекта Catlair для Pusa.
    Выполняет проверку Документа относительно Предоставленной схемы.

    Автор: still@itserv.ru
    Форк: https://gitlab.com/catlair/pusa/-/blob/main/php/core/checkup_request.php
*/
namespace catlair;



require_once 'checkup.php';



class TCheckupRequest extends TCheckup
{
    /*
        Создание валидатора
    */
    public static function create()
    {
        return new TCheckupRequest();
    }



    /*
        Валидация запроса
    */
    public function checkupRequest
    (
        string $AURL    = '',
        array $APost    = null,
        array $ACookie  = null,
        array $AServer  = null
    )
    {
        if( $this -> isOk() )
        {
            $URL = parse_url( $AURL );
            if( $this -> ifResult( !empty( $URL ) ) -> isOk() )
            {
                parse_str( clValueFromObject( $URL, 'query' ), $Get );
                $Document =
                [
                    'get'       => empty( $Get ) ? $_GET : $Get,
                    'post'      => empty( $APost ) ? $_POST : $APost,
                    'cookies'   => empty( $ACookie ) ? $_COOKIE : $ACookie,
                    'protocol'  => $URL[ 'scheme' ],
                    'host'      => $URL[ 'host' ]
                ];
                /* Заполнение параметров URL */
                if( array_key_exists( 'port', $URL )) $Document[ 'port' ] = $URL[ 'port' ];
                if( array_key_exists( 'user', $URL )) $Document[ 'user' ] = $URL[ 'user' ];
                if( array_key_exists( 'pass', $URL )) $Document[ 'password' ] = $URL[ 'pass' ];
                if( array_key_exists( 'path', $URL )) $Document[ 'path' ] = explode( '/', $URL[ 'path' ] );
                if( array_key_exists( 'fragment', $URL )) $Document[ 'anchor' ] = $URL[ 'fragment' ];
                $this -> checkup( $Document );
            }
        }
        return $this;
    }



    /*
        Установка схемы валидации из строки JSON
        Выполняется конвертация переданной строки в массив
    */
    public function setSchemeJSON
    (
        string $AValue = null
    )
    {
        if( $this -> isOk() )
        {
            $Scheme = json_decode( $AValue, true );
            if( $this -> ifResult( gettype( $Scheme ) != 'array', 'scheme_is_invalid', [ 'scheme_source' => $AValue ] ) -> isOK() )
            {
                $this -> setScheme( $Scheme );
            }
        }
        return $this;
    }



    /*
        Валидация JSON документа
    */
    public function validateJSON
    (
        string $ADocument = ''
    )
    {
        if( $this -> isOk() )
        {
            $Document = json_decode( $ADocument, true );
            if( $this -> ifResult( $Document === null, 'document_is_invalid' ) -> isOk() )
            {
                $this -> validate( $Document );
            }
        }
        return $this;
    }
}
