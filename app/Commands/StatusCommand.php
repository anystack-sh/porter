<?php

namespace App\Commands;

use App\Commands\Concerns\HiddenProcesses;
use App\Repositories\SupervisordRepository;
use LaravelZero\Framework\Commands\Command;

class StatusCommand extends Command
{
    use HiddenProcesses;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'status {--all}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'View status of processes';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(SupervisordRepository $repository)
    {
        $this->ensurePorterIsRunning();

        $this->table(
            ['App', 'Process', 'Status', 'Description'],
            $repository->getAllProcessInfo()
                ->reject(fn ($val) => ! $this->option('all') && in_array($val['name'], $this->hiddenProcesses, true))
                ->map(function ($val) {
                    return [$val['group'], $val['name'], $val['statename'], $val['description']];
                })->sortBy(fn ($v) => $v[0])
        );
    }
}
