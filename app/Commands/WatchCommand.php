<?php

namespace App\Commands;

use App\Repositories\ConfigRepository;
use App\Repositories\SupervisordRepository;
use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class WatchCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'watch';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Watch for file changes';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(ConfigRepository $configRepository)
    {
        // Todo: Make this more readable
        $processes = $configRepository->getApplicationConfigurations()->map(function ($app) {
            return collect($app['config']['services'] ?? [])->map(function ($service) use ($app) {
                return [
                    'process_name' => $service['process_name'],
                    'paths' => collect($service['restart']['watch'] ?? [])->map(fn ($path) => $app['dir'].DIRECTORY_SEPARATOR.$path),
                ];
            })->reject(fn ($service) => blank($service['paths'] ?? null));
        })->flatten(1);

        $watcher = tap(new Process([
            (new ExecutableFinder)->find('node'),
            Storage::path('file-watcher.js'),
            $processes->pluck('paths')->toJson(),
        ], null, null, null, null))->start();

        while (true) {
            if ($watcher->isRunning() &&
                $path = $watcher->getIncrementalOutput()) {
                $processes->where(function ($process) use ($path) {
                    return collect($process['paths'])->reject(function ($p) use ($path) {
                        return str($path)->contains($p);
                    });
                })->pluck('process_name')->each(function ($processName) use ($path) {
                    $this->line(str('File change detected: '.$path)->squish());
                    $this->comment("Restarting {$processName}…");
                    $this->app->make(SupervisordRepository::class)->restartProcess($processName);
                });
            } elseif ($watcher->isTerminated()) {
                $this->error(
                    'Watcher process has terminated. Please ensure Node and chokidar are installed.'.PHP_EOL.
                    $watcher->getErrorOutput()
                );

                return 1;
            }
        }
    }
}
