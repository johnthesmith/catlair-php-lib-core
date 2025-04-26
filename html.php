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


namespace catlair;
/*
    The basic class for HTML builder.
    Heracly: HTML < Text < TResult

    2019-11-22 still@itserv.ru
*/

require_once 'text.php';
require_once 'tag.php';



class HTML extends Text
{
    /* Private declarations */
    private $tags       = [];



    static function Create()
    {
        $Result = new self();
        return $Result;
    }



    /*
        Open new tag
    */
    public function &TagOpen( $aTagName )
    {
        $newTag = new TTag();
        $newTag -> tagName = $aTagName;
        array_push( $this -> tags, $newTag );
        return $this;
    }



    /*
        Close all tag
    */
    public function &TagCloseAll()
    {
        while( count( $this -> tags ) > 0 )
        {
            $this -> TagClose();
        }
        return $this;
    }



    /*
        Close tag
    */
    public function &TagClose()
    {
        if ( count( $this -> tags ) == 0 )
        {
            $this -> code = 'NoOpenTags';
        }
        else
        {
            $tag = array_pop( $this -> tags );

            $buffer = '';
            $buffer .= $tag -> GetHead();
            $buffer .= $tag -> GetContent();
            $buffer .= $tag -> GetTail();

            /* return result */
            $tagCurrent = $this -> TagCurrent();
            if ( $tagCurrent == null )
            {
                /* set result in to main buffer */
                $this -> Add( $buffer );
            }
            else
            {
                /* set result in to current tag */
                $tagCurrent -> Add( $buffer );
            }
        }
        return $this;
    }



    /*
        Return current tag
    */
    public function &TagCurrent()
    {
        $count = count( $this -> tags );
        if ( $count > 0 )
        {
            $result = $this -> tags[ $count - 1 ];
        }
        else
        {
            $result = null;
        }
        return $result;
    }



    public function IsTag( $aTagName )
    {
        $tag = $this -> TagCurrent();
        return !empty( $tag ) && $tag -> tagName == $aTagName;
    }



    /*
        Add content to tag
    */
    public function AddContent
    (
        string $aContent = '',
        array $aReplace = []
    )
    {
        if ($this -> isOk())
        {
            foreach( $aReplace as $key => $value )
            {
                $aContent = str_replace( '%' . $key . '%', $value, $aContent );
            }

            $tagCurrent = $this -> TagCurrent();
            if ( $tagCurrent == null )
            {
                $this -> Add( $aContent );
            }
            else
            {
                /* Set attrubute in to tag*/
                $tagCurrent -> Add( $aContent );
            }
        }
        return $this;
    }



    /*
        Open tag, put content, and close tag
    */
    public function &TagContent($aTag, $aContent)
    {
        if ($this -> isOk())
        {
            $this
            -> TagOpen($aTag)
            -> AddContent($aContent)
            -> TagClose();
        }
        return $this;
    }



    /*
        Set atrubute to current tag
    */
    public function &SetAttr( $aName, $aValue )
    {
        if ( $this -> isOk() )
        {
            $tagCurrent = $this -> TagCurrent();
            if ( $tagCurrent == null )
            {
                $this -> code = 'TagNotFound';
            }
            else
            {
                /* Set attrubute in to tag*/
                $tagCurrent -> attrs[ $aName ] = $aValue;
            }
        }
        return $this;
    }



    /*
        Set atrubute to current tag
    */
    public function &SetClass( $aValue )
    {
        return $this
        -> setAttr ( 'class', $aValue );
    }



    /*
        Set atrubute to current tag
    */
    public function &Tag( $aValue )
    {
        return $this
        -> TagOpen( $aValue )
        -> TagClose();
    }
}
