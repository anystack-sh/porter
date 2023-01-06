<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;

class ConfigRepository
{
    public Collection $apps;

    public function __construct()
    {
        $this->apps = $this->getApplicationConfigurations();
    }

    public function addApplication($path)
    {
        $directories = $this->apps->pluck('dir');

        if ($directories->search($path) === false) {
            $directories->push($path);

            Storage::put('apps.json', $directories->map(fn ($dir) => compact('dir'))->toJson());
        }
    }

    public function removeApplication($path)
    {
        $directories = $this->apps->pluck('dir');

        if (($index = $directories->search($path)) !== false) {
            Storage::put('apps.json', $directories->forget($index)->values()->map(fn ($dir) => compact('dir'))->toJson());
        }
    }

    public function getApplicationConfigurations(): Collection
    {
        $this->initializeConfiguration();
        $this->initializeFileWatcher();
        $this->initializeLogDirectory();

        return $this->readJsonConfig('apps.json')
            ->map(fn ($app) => $this->appendYmlConfig($app))
            ->map(function ($app) {
                foreach ($app['config']['services'] ?? [] as $index => &$service) {
                    $service['program'] = $this->generateProgramName($app['name'], $service['name']);
                    $service['process_name'] = $this->generateProcessName($app['name'], $service['program']);

                    $app['config']['services'][$index] = $service;
                }

                return $app;
            });
    }

    public function appendYmlConfig($app): array
    {
        return array_merge($app, [
            'name' => str($app['dir'])->afterLast('/')->toString(),
            'config' => $this->readYmlConfig($app['dir'].'/porter.yml'),
        ]);
    }

    public function readJsonConfig($filename): Collection
    {
        try {
            $config = json_decode(
                Storage::get($filename),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\Exception $exception) {
            $config = [];
        }

        return collect($config);
    }

    public function readYmlConfig($filename)
    {
        if (! file_exists($filename)) {
            return [];
        }

        return Yaml::parseFile($filename);
    }

    public function initializeConfiguration(): void
    {
        if (Storage::exists('apps.json') === false) {
            Storage::put('apps.json', '{}');
        }
    }

    public function initializeLogDirectory()
    {
        if (Storage::exists('logs') === false) {
            Storage::makeDirectory('logs');
        }
    }

    private function initializeFileWatcher()
    {
        if (Storage::exists('file-watcher.js') === false) {
            Storage::put('file-watcher.js', file_get_contents(config_path('stubs/file-watcher.js')));
        }
    }

    public function createStub()
    {
        return file_put_contents('porter.yml', file_get_contents(config_path('stubs/porter.yml')));
    }

    public function porterBinaryPath(): string
    {
        if (\Phar::running()) {
            return str(base_path())->replace('phar://', '')->toString();
        }

        return base_path('porter');
    }

    public function writeSupervisordConfiguration()
    {
        $programs = $this->buildSupervisordProgramsConfig(
            $this->getApplicationConfigurations()
        );

        $schedulePath = $this->porterBinaryPath();

        $config = str(file_get_contents(config_path('stubs/supervisord.conf')))
            ->replace('$STORAGE_PATH', $this->storagePath('/'))
            ->replace('$SCHEDULE_PATH', $schedulePath)
            ->append(PHP_EOL)
            ->append($programs)
            ->toString();

        Storage::put('supervisord.conf', $config);
    }

    private function buildSupervisordProgramsConfig($apps): string
    {
        return collect($apps)->map(function ($app) {
            $services = $app['config']['services'] ?? [];
            $programs = collect($services)->pluck('program')->implode(',');

            return collect($services)->map(function ($service) use ($app) {
                $programName = str($service['program']);
                $logFilename = $programName->append('.log');
                $errorLogFilename = $programName->append('-error.log');

                return str(file_get_contents(config_path('stubs/program.conf')))
                    ->when(
                        isset($service['directory']),
                        fn ($str) => $str->replace('$DIR', $service['directory']),
                        fn ($str) => $str->replace('$DIR', $app['dir']),
                    )
                    ->replace('$NAME', $programName)
                    ->replace('$COMMAND', $service['command'])
                    ->replace('$LOGFILE', $this->storagePath("logs/{$logFilename}"))
                    ->replace('$ERRORFILE', $this->storagePath("logs/{$errorLogFilename}"))
                    ->toString();
            })->implode(PHP_EOL).str(file_get_contents(config_path('stubs/group.conf')))
                    ->replace('$NAME', str($app['name'])->slug())
                    ->replace('$PROGRAMS', $programs);
        })->implode(PHP_EOL);
    }

    private function generateProcessName($appName, $programNam)
    {
        return str($appName)->slug()
            ->append(':')
            ->append($programNam)
            ->toString();
    }

    private function generateProgramName($appName, $serviceName)
    {
        return str($appName)->slug()
            ->append('-')
            ->append(str($serviceName)->slug())
            ->toString();
    }

    private function storagePath($path = '')
    {
        return Storage::path($path);
    }
}
