<?php

return [
    'name' => 'MarkingManagement',
    
    /*
    |--------------------------------------------------------------------------
    | Marking Duration
    |--------------------------------------------------------------------------
    |
    | Default duration for marking expiration in days.
    | Can be overridden by system settings.
    |
    */
    'marking_duration_days' => 3,
    
    /*
    |--------------------------------------------------------------------------
    | Maximum Extension Days
    |--------------------------------------------------------------------------
    |
    | Maximum number of days a marking can be extended.
    |
    */
    'max_extension_days' => 7,
    
    /*
    |--------------------------------------------------------------------------
    | Expiration Warning Hours
    |--------------------------------------------------------------------------
    |
    | Hours before expiration to send warning notification.
    |
    */
    'expiration_warning_hours' => 24,
];
