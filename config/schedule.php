<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Scheduled Tasks
    |--------------------------------------------------------------------------
    |
    | Define your scheduled tasks here. Tasks will be run by the scheduler
    | when the schedule:run command is executed.
    |
    */

    'tasks' => [
        // Example: Run a command every minute
        // [
        //     'command' => 'logs:clear',
        //     'frequency' => '* * * * *',
        //     'description' => 'Clear old logs',
        // ],

        // Example: Run a callable every day at midnight
        // [
        //     'callable' => function() {
        //         // Your code here
        //     },
        //     'frequency' => '0 0 * * *',
        //     'description' => 'Daily maintenance',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    |
    | The timezone in which scheduled tasks will run.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Task Overlap Prevention
    |--------------------------------------------------------------------------
    |
    | Prevent tasks from overlapping by using file-based locks.
    |
    */

    'prevent_overlap' => true,

    /*
    |--------------------------------------------------------------------------
    | Lock Path
    |--------------------------------------------------------------------------
    |
    | Path where lock files will be stored.
    |
    */

    'lock_path' => __DIR__ . '/../storage/framework/schedule',
];
