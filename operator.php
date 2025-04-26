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
    Catlair logical operators
*/

namespace catlair;

class TOperator
{
    const NOT                   = 'not';
    const OR                    = 'or';
    const AND                   = 'and';
    const LIKE                  = 'like';
    const NOT_LIKE              = 'not like';
    const IN                    = 'in';
    const NOT_IN                = 'not in';
    const EQUAL                 = '=';
    const NOT_EQUAL             = '<>';
    const MORE                  = '>';
    const MORE_EQUAL            = '>=';
    const LESS                  = '<';
    const LESS_EQUAL            = '<=';
    const FULL_TEXT             = 'full text';
}
