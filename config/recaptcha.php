<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google reCAPTCHA v2 (checkbox)
    |--------------------------------------------------------------------------
    |
    | Register keys at https://www.google.com/recaptcha/admin — choose
    | reCAPTCHA v2 → "I'm not a robot" Checkbox.
    |
    | Leave RECAPTCHA_ENABLED=false locally unless you are testing the widget.
    |
    */

    'enabled' => filter_var(env('RECAPTCHA_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'site_key' => env('RECAPTCHA_SITE_KEY', ''),

    'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),

];
