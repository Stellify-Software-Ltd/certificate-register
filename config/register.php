<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Operator key
    |--------------------------------------------------------------------------
    |
    | A single shared key that opens the register. Set OPERATOR_KEY in .env;
    | sign-in is refused while it is empty. Sign-in attempts are rate-limited
    | per IP and failures are logged.
    |
    */

    'operator_key' => env('OPERATOR_KEY', ''),

];
