<?php namespace NGAFID\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		'NGAFID\Console\Commands\Inspire',
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		//$schedule->command('inspire')
		//		 ->hourly();

        //automatically handle web import when called by the CRON job
        $result = \DB::select("SELECT count(`id`) AS 'count' FROM fdmdm.`jobs`");
        $count = $result[0]->count;

        if($count > 0){
            $schedule->command('queue:work')->everyThirtyMinutes();//->withoutOverlapping();
        }
	}

}
