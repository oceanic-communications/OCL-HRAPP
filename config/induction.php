<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HR notification email
    |--------------------------------------------------------------------------
    |
    | When an employee completes induction, the full acknowledgement PDF (section
    | content and per-section employee sign-off) is emailed to the employee and
    | separately to this HR address. Leave null to skip the HR email.
    |
    */
    'hr_notification_email' => env('INDUCTION_HR_EMAIL'),

    'numbering_scheme_defaults' => [
        'section' => [
            'style' => 'roman',
            'separator' => '.',
            'start' => 'I',
        ],
        'clause' => [
            'style' => 'alpha_upper',
            'separator' => '.',
            'start' => 'A',
            'inherit_preview' => 'II.A',
        ],
        'sub_clause' => [
            'style' => 'decimal',
            'separator' => '.',
            'prefix' => '',
            'start' => '1',
        ],
    ],

];
