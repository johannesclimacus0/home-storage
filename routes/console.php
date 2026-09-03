<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('inventory:send-low-stock-reminders')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('telegram:send-due-reminders')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('horizon:snapshot')
    ->everyFiveMinutes();
