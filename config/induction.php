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

];
