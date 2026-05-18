<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Log per-request DB profile (development)
    |--------------------------------------------------------------------------
    |
    | When true, the web stack logs one JSON line per HTML request to the
    | portal_profile log channel: wall time, query count, duplicate SQL shapes,
    | and the slowest statements. Enable with PROFILE_PORTAL_REQUESTS=true in .env.
    |
    */

    'profile_requests' => filter_var(
        env('PROFILE_PORTAL_REQUESTS', false),
        FILTER_VALIDATE_BOOL
    ),

];
