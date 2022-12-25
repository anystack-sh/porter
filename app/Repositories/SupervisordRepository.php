<?php

namespace App\Repositories;

use App\Exceptions\SupervisordAbnormalTerminationException;
use App\Exceptions\SupervisordAlreadyAddedException;
use App\Exceptions\SupervisordAlreadyStartedException;
use App\Exceptions\SupervisordBadArgumentsException;
use App\Exceptions\SupervisordBadNameException;
use App\Exceptions\SupervisordBadSignalException;
use App\Exceptions\SupervisordCannotRereadException;
use App\Exceptions\SupervisordConnectionRefusedException;
use App\Exceptions\SupervisordFailedException;
use App\Exceptions\SupervisordIncorrectParametersException;
use App\Exceptions\SupervisordIsAlreadyRunningException;
use App\Exceptions\SupervisordNoFileException;
use App\Exceptions\SupervisordNotExecutableException;
use App\Exceptions\SupervisordNotInstalledException;
use App\Exceptions\SupervisordNotRunningException;
use App\Exceptions\SupervisordShutdownStateException;
use App\Exceptions\SupervisordSignatureUnsupported;
use App\Exceptions\SupervisordSpawnErrorException;
use App\Exceptions\SupervisordStillRunningException;
use App\Exceptions\SupervisordUnknownMethodException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpXmlRpc\Client;
use PhpXmlRpc\Encoder;
use PhpXmlRpc\Request;
use Symfony\Component\Process\ExecutableFinder;

class SupervisordRepository
{
    private Client $client;

    private Encoder $encoder;

    public function __construct()
    {
        $this->encoder = new Encoder();
        $this->client = new Client('RPC2', '127.0.0.1', 9125);
        $this->client->username = 'launcher';
        $this->client->password = 'secret';
    }

    public function reloadConfig()
    {
        return $this->call('reloadConfig');
    }

    public function restart()
    {
        return $this->call('restart');
    }

    public function getAllProcessInfo()
    {
        return $this->call('getAllProcessInfo');
    }

    public function getProcessInfo($name)
    {
        return $this->call('getProcessInfo', $name);
    }

    public function stopAllProcesses()
    {
        return $this->call('stopAllProcesses');
    }

    public function restartProcess($name)
    {
        try {
            $this->call('stopProcess', $name);

            $this->waitForProcessState($name, 'STOPPED');

            $this->call('startProcess', $name);
        } catch (SupervisordNotRunningException $e) {
            dd('fail');
        }
    }

    private function waitForProcessState($name, $state, $timeout = 5)
    {
        $i = 0;
        while ($i <= $timeout) {
            if ($this->getProcessInfo($name)->get('statename') === $state) {
                break;
            }

            sleep(1);

            $i++;
        }

        return true;
    }

    public function startSupervisord()
    {
        Storage::delete(
            Storage::allFiles('logs')
        );

        if (Storage::exists('supervisord.pid')) {
            throw new SupervisordIsAlreadyRunningException('Services are already running.');
        }

        $supervisordPath = (new ExecutableFinder())->find('supervisord');

        if (is_null($supervisordPath)) {
            throw new SupervisordNotInstalledException('Supervisord is not installed. Run "brew install supervisor".');
        }

        exec($supervisordPath.' -c '.Storage::disk()->path('supervisord.conf'));
    }

    public function stopSupervisord()
    {
        if (Storage::exists('supervisord.pid') === false) {
            return true;
        }

        posix_kill(Storage::get('supervisord.pid'), SIGTERM);

        while (true) {
            if (file_exists(Storage::path('supervisord.pid')) === false) {
                break;
            }
        }

        return true;
    }

    private function call($method, $params = [])
    {
        $params = $this->encodeParameters(Arr::wrap($params));

        $response = $this->client->send(new Request("supervisor.$method", $params));

        if ($response->faultCode()) {
            match ($response->faultCode()) {
                1 => throw new SupervisordUnknownMethodException(),
                2 => throw new SupervisordIncorrectParametersException(),
                3 => throw new SupervisordBadArgumentsException(),
                4 => throw new SupervisordSignatureUnsupported(),
                5 => throw new SupervisordConnectionRefusedException(),
                6 => throw new SupervisordShutdownStateException(),
                10 => throw new SupervisordBadNameException(),
                11 => throw new SupervisordBadSignalException(),
                20 => throw new SupervisordNoFileException(),
                21 => throw new SupervisordNotExecutableException(),
                30 => throw new SupervisordFailedException(),
                40 => throw new SupervisordAbnormalTerminationException(),
                50 => throw new SupervisordSpawnErrorException(),
                60 => throw new SupervisordAlreadyStartedException(),
                70 => throw new SupervisordNotRunningException(),
                90 => throw new SupervisordAlreadyAddedException(),
                91 => throw new SupervisordStillRunningException(),
                92 => throw new SupervisordCannotRereadException(),
                default => throw new SupervisordFailedException("[{$response->faultCode()}] {$response->faultString()}")
            };
        }

        return $this->decodeValues($response->value());
    }

    private function decodeValues($values): Collection
    {
        return collect($values)->map(function ($val) {
            return (is_bool($val)) ? $val : $this->encoder->decode($val);
        });
    }

    private function encodeParameters(array $params): array
    {
        return collect($params)->map(function ($param) {
            return $this->encoder->encode($param);
        })->toArray();
    }
}
