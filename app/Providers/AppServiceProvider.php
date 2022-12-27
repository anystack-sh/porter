<?php

namespace App\Providers;

use App\Repositories\SupervisordRepository;
use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Command::macro('ensurePorterIsRunning', function () {
            $supervisord = app(SupervisordRepository::class);

            if ($supervisord->isSupervisordRunning() === false) {
                if ($this->confirm('Porter must be running to use this command. Do you want to start Porter?', true)) {
                    return $supervisord->startSupervisord();
                }

                exit;
            }
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
