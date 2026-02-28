<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default SMS driver
    |--------------------------------------------------------------------------
    |
    | Options (for now):
    |   - fake            : just logs to laravel.log (for testing)
    |   - hostpinnacle    : HostPinnacle SMS (global credits)
    |   - africastalking  : TODO
    |   - twilio          : TODO
    |
    */

    'driver' => env('SMS_DRIVER', 'fake'),

    /*
    |--------------------------------------------------------------------------
    | Driver configurations
    |--------------------------------------------------------------------------
    */

    'drivers' => [

        'fake' => [
            // no config needed
        ],

        'hostpinnacle' => [
            // HostPinnacle base URL + send endpoint.
            // Docs commonly show: https://smsportal.hostpinnacle.co.ke/SMSApi/send
            // We'll allow overriding fully from env, but keep sensible defaults.
            'base_url'  => env('HOSTPINNACLE_SMS_BASE_URL', 'https://smsportal.hostpinnacle.co.ke'),
            'send_path' => env('HOSTPINNACLE_SMS_SEND_PATH', '/SMSApi/send'),

            // Preferred auth: API key header "apikey"
            'api_key' => env('HOSTPINNACLE_SMS_API_KEY'),

            // Optional fallback auth if you ever need it (only used if api_key is empty)
            'user_id'  => env('HOSTPINNACLE_SMS_USER_ID'),
            'password' => env('HOSTPINNACLE_SMS_PASSWORD'),

            // SenderId handling:
            // - If set, we'll send senderid=<value>
            // - If empty/null, we OMIT senderid and rely on gateway default (IF supported).
            //
            // NOTE: HostPinnacle docs list senderid as required, so if the API rejects missing senderid
            // you must set this env or your sends will fail (but will be logged).
            'from' => env('HOSTPINNACLE_SMS_SENDER', env('SMS_FROM', null)),

            // HostPinnacle parameters
            'send_method' => env('HOSTPINNACLE_SMS_SEND_METHOD', 'quick'), // quick recommended
            'msg_type'    => env('HOSTPINNACLE_SMS_MSG_TYPE', 'text'),     // text|unicode
            'output'      => env('HOSTPINNACLE_SMS_OUTPUT', 'json'),       // json

            'timeout_seconds' => (int) env('HOSTPINNACLE_SMS_TIMEOUT', 15),
        ],

        'africastalking' => [
            'username' => env('AT_USERNAME'),
            'api_key'  => env('AT_API_KEY'),
            'from'     => env('AT_FROM', 'GarageSuite'),
        ],

        'twilio' => [
            'sid'   => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from'  => env('TWILIO_FROM'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Milestones
    |--------------------------------------------------------------------------
    | Expiry milestones for garage notifications:
    |   7,3,1 days before; 0 day-of; -3 days after expiry
    |
    */

    'expiry_milestones' => [7, 3, 1, 0, -3],

    /*
    |--------------------------------------------------------------------------
    | Invoice due milestones for customer reminders:
    |   2 days before; day-of; 3 days overdue
    |
    */
    'invoice_due_milestones' => [2, 0, -3],

];
