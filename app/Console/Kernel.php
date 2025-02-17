<?php

namespace App\Console;

use App\Http\Controllers\QrCodeController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('app:delete-old-qrcodes')->daily();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');    }
}
