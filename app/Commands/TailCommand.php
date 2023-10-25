<?php

namespace App\Commands;

use App\Repositories\ConfigRepository;
use App\Repositories\SupervisordRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;

class TailCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'tail
                            {app?}
                            {--services= : Comma-separated list of service indexes (e.g. 0,1) or service names to tail}
                            {--all}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Tail service log files';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(ConfigRepository $configRepository, SupervisordRepository $supervisordRepository)
    {
        $this->ensurePorterIsRunning();

        $app = $this->resolveAppToTail($configRepository);

        $appServices = $supervisordRepository->getAllProcessInfo()->where('group', $app);

        if ($this->option('all') === false) {
            $servicesToTail = $this->resolveServicesToTail($appServices);
        }

        $files = $appServices
            ->when(isset($servicesToTail), fn ($c) => $c->whereIn('name', $servicesToTail))
            ->pluck('stdout_logfile');

        $this->comment('Use CTRL+C to stop tailing.');

        $this->tail($files);
    }

    private function resolveAppToTail(ConfigRepository $config): mixed
    {
        return $this->argument('app') ??
               $this->getAppFromCwd($config) ??
               $this->choice(
                   'Which app do you want to tail?',
                   $config->apps->pluck('name')
                                ->map(fn ($name) => str($name)->slug())
                                ->toArray(),
               );
    }

    private function getAppFromCwd(ConfigRepository $config): ?string
    {
        $app = $config->apps->firstWhere('dir', getcwd())['name'] ?? null;

        return $app !== null ? str($app)->slug() : null;
    }

    private function resolveServicesToTail(Collection $appServices): mixed
    {
        return $this->getServicesFromOption($appServices) ??
            $this->choice(
                'Which service do you want to tail?',
                $appServices->pluck('name')->toArray(),
                null,
                null,
                true
            );
    }

    private function getServicesFromOption(Collection $appServices): mixed
    {
        $services = $this->option('services');

        if (empty($services)) {
            return null;
        }

        return Str::of($services)
            ->explode(',')
            ->map(function ($service) use ($appServices) {
                if (is_numeric($service) && isset($appServices[$service])) {
                    return $appServices[$service]['name'];
                }

                if ($appServices->contains('name', $service)) {
                    return $service;
                }

                throw new InvalidArgumentException("Service \"$service\" is invalid");
            })
            ->filter()
            ->unique()
            ->toArray() ?: null;
    }

    private function tail(Collection $files)
    {
        foreach ($files as $index => $file) {
            $name = 'file'.$index;
            $$name = fopen($file, 'r');
            fseek($$name, -1, SEEK_END);
        }

        while (true) {
            foreach ($files as $index => $file) {
                $name = 'file'.$index;
                $line = fgets($$name);

                if ($line !== false) {
                    $this->line($line);
                } else {
                    usleep(0.1 * 1000000);
                    fseek($$name, ftell($$name));
                }
            }
        }
    }
}
