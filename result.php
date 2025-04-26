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
    Base class for storing the system's result (state)
    Used as the foundation for other classes.
*/



/* Local libraires */
include 'utils.php';


class Result
{
    /* Default result code */
    const RC_OK = 'ok';


    /*
        Private properties of the state
    */

    /* Default result value */
    private $Code        = self::RC_OK;
    /*
        Accumulating named array of details for the current state in
        key=>value format
    */
    private $Details     = [];
    /*
        The current state is informational. It is stored in the object but
        will always return RC_OK during all checks
    */
    private $OkState    = [ self::RC_OK ];




    /*
        Creates state from array
        result:
            code:
            details:
    */
    static public function fromArray
    (
        /* Массив источник */
        array $aSource = []
    )
    {
        $result = new Result();
        return $result -> setResultFromArray( $aSource );
    }


    /*
        Установка результата из массива
        result:
            code:
            details:
    */
    public function setResultFromArray
    (
        array $aSource
    )
    {
        $this -> setResult
        (
            (string) clValueFromObject
            (
                $aSource,
                [ 'result', 'code' ],
                'unknown_error'
            ),
            (array) clValueFromObject
            (
                $aSource,
                [ 'result', 'details' ],
                []
            )
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
            'result' =>
            [
                'code'    => $this -> getCode(),
                'details' => $this -> getDetails()
            ]
        ];
    }




    /*
        Returns true if the result is Ok (Code is present in OkState)
     */
    public function isOk() : bool
    {
        /* Checks if the current code is present in the OkState array */
        return in_array( $this -> Code, $this -> OkState );
    }



    /*
        Returns true for Code == ACode
    */
    public function isCode
    (
        /* Code for check */
        string $aCode
    )
    : bool
    {
        return $this -> getCode() == $aCode;
    }



    /*
        Sets the result state to "no errors"
    */
    public function setOk
    (
        /*
            If the current error is in the provided list or the list is empty,
            the result will be set to OK
        */
        array $aOkCode = []
    )
    {
        if
        (
            count( $aOkCode ) == 0 ||
            in_array( $this -> Code, $aOkCode )
        )
        {
            $this -> setCode( self::RC_OK );
        }
        return $this;
    }



    /*
        Устанавливается параметр детализации ошибки
    */
    public function setDetail
    (
        /* Имя параметра детализации состояния */
        $aKeyPath,
        /* Значение параметра детализации состояния */
        $aValue = null
    )
    {
        clValueToObject( $this -> Details, $aKeyPath, $aValue );
        return $this;
    }



    /*
        Returns the value of the detail key
    */
    public function getDetail
    (
        /* Key name as a string or path as array of string */
        string $aKeyPath,
        /* Default value */
        $aDefault = null
    )
    {
        return clValueFromObject( $this -> Details, $aKeyPath, $aDefault );
    }




    /*
        Sets the result
     */
    public function setResult
    (
        /* State code */
        string $aCode     = self::RC_OK,
        /* Details array */
        array $aDetails    = []
    )
    {
        $this -> setCode( $aCode );
        $this -> Details = $aDetails;
        return $this;
    }



    /*
        Validates the result
    */
    public function validate
    (
        /* Validation condition, if true, error will be set */
        bool    $aNotValidate   = false,
        /* Error code */
        string  $aCode          = self::RC_OK,
        /* Error details array, which will be merged with the previous one */
        array   $aDetails       = []
    )
    {
        if( $this -> isOk() && $aNotValidate )
        {
            $this -> setResult( $aCode, $aDetails );
        }
        return $this;
    }




    /*
        Transfers the result from the source to the current object
    */
    public function resultFrom
    (
        /* Source object to get the result state from */
        Result &$aSource,
        /* Error prefix to be added during the transfer */
        string $aPrefix = null
    )
    {
        $this -> setResult
        (
            ( empty( $aPrefix ) ? '' : $aPrefix ) . $aSource -> Code,
            $aSource -> Details
        );
        return $this;
    }



    /*
        Transfers the result from the current object to the target object
    */
    public function resultTo
    (
        /* Target object to get the result state from */
        Result &$aTarget,
        /* Error prefix to be added during the transfer */
        string $aPrefix = null
    )
    {
        // Transfers the result from the current object to the target object
        return $aTarget -> resultFrom( $this, $aPrefix );
    }




    /*
        Setters and getters
    */




    /*
        Устанавливает код ошибки
    */
    public function setCode
    (
        /* Значение кода ошибки */
        string  $aValue,
    )
    :self
    {
        $this -> Code = $aValue;
        return $this;
    }



    /*
        Get result code
    */
    public function getCode()
    {
        return $this -> Code;
    }



    /*
        Return details array
    */
    public function getDetails()
    {
        return $Result = $this -> Details;
    }




    /*
        Returns the value of OkState
     */
    public function getOkState() : array
    {
        return $this -> OkState;
    }



    /*
        Sets the value of OkState
     */
    public function setOkState
    (
        array $aOkState
    )
    : self
    {
        $this -> OkState = $aOkState;
        return $this;
    }
}
