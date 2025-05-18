1. в result добавить инклюд утилсов
2. в утилс заменить регулярку что бы - понимал
    902         $Source = preg_split( '/(' . $ABegin . '[\w.\-]*' . $AEnd . ')/', $Result, 0, PREG_SPLIT_DELIM_CAPTURE );
    919                 if( preg_match( '/' . $ABegin. '[\w.\-]*' . $AEnd .  '/', $Lexeme ))

