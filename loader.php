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



require_once 'result.php';



class Loader extends Result
{
    function __construct()
    {
        $this -> SetOk();
    }



    static public function create()
    {
        return new self();
    }



    public function load(  $ALibrary )
    {
        if( $this -> isOk() )
        {
            if( !file_exists( $ALibrary ))
            {
                $this -> SetResult
                (
                    'LibraryNotFound',
                    [ 'Library' => $ALibrary ],
                    'Library not found'
                );
            }
            else
            {
                try
                {
                    /* Loading the library */
                    require_once( $ALibrary );
                }
                catch( \Throwable $Error )
                {
                    $this -> SetResult
                    (
                        'LibraryLoadError',
                        [
                            'Library' => $ALibrary,
                            'Message' => $Error -> getMessage(),
                            'File' => $Error -> getFile(),
                            'Line' => $Error -> getLine()
                        ]
                    );
                }
            }
        }
        return $this;
    }

}




