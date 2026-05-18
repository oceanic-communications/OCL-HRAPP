<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HR notification email
    |--------------------------------------------------------------------------
    |
    | When an employee completes induction, a copy of the acknowledgement PDF
    | is sent to this address (in addition to the employee). Leave null to skip.
    |
    */
    'hr_notification_email' => env('INDUCTION_HR_EMAIL'),

];
