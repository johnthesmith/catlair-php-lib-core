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
    Tag class
    Contein basic methods for tag
    Heracly: TTag < TResult
    2019-11-20 still@itserv.ru
*/

require_once 'result.php';
require_once 'text.php';



class TTag extends Text
{
    public $attrs   = [];
    public $tagName = '';



    public function GetHead()
    {
        $result = '<' . $this -> tagName;
        if ( count( $this -> attrs ) > 0 )
        {
            $attrs = [];
            foreach ( $this->attrs as $param => $value )
            {
                array_push( $attrs, $param . '="' . $value . '"' );
            }
            $result .= ' ' . implode(' ', $attrs);
        }
        if ( ! $this -> IsVoid() ) $result .= '>';
        return $result;
    }



    public function GetTail()
    {
        if ( ! $this -> IsVoid() ) $result = '</' . $this -> tagName . '>';
        else $result = '/>';
        return $result;
    }



    /*
        Check tag for void
    */
    public function IsVoid()
    {
        switch ( $this -> tagName )
        {
            case 'area':
            case 'base':
            case 'br':
            case 'col':
            case 'command':
            case 'embed':
            case 'hr':
            case 'img':
            case 'input':
            case 'keygen':
            case 'link':
            case 'meta':
            case 'param':
            case 'source':
            case 'track':
            case 'wbr':
                $result = true;
            break;
            default:
                $result = false;
            break;
        }
        return $result;
    }
}
