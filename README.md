<p align="center">
<a href="https://packagist.org/packages/anystack-sh/porter"><img src="https://img.shields.io/packagist/dt/anystack-sh/porter" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/anystack-sh/porter"><img src="https://img.shields.io/packagist/v/anystack-sh/porter" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/anystack-sh/porter"><img src="https://img.shields.io/packagist/l/anystack-sh/porter" alt="License"></a>
</p>

## About Porter

Porter is a command-line interface (CLI) tool that makes it easy to run background services by adding a few lines to a configuration file.
These services are managed by Supervisord, a process control system that ensures that all processes are kept up and running. 
With Porter, you don't have to manually start and manage background services in multiple terminal tabs. 
Instead, you can simply use the `porter` command to manage all of your services in a single place.

### Installation

To install Porter, you can use `composer` or download the build manually from this repository.

```shell
composer global require anystack-sh/porter
```

#### Requirements
To use Porter you must install `supervisord`:
- macOS: `brew install supervisor`
- Linux: `apt install supervisor`

If you want to use the watch feature to restart services when files change you will also need to install:
- chokidar: `npm install --global chokidar`

### Add your first project
In your terminal navigate to your project and run `porter init` to create a boilerplate `porter.yml`:

```shell
~/Developer/anystack: $ porter init

 Create porter.yml in /Users/Developer/anystack? (yes/no) [yes]:
 > yes

Creating porter.yml boilerplate: ✔
Run "porter add" to add your product and start your services.
```

Modify `porter.yml` and add the services you want to run. In your terminal navigate to your project and run `porter add`:

```shell
~/Developer/anystack: $ porter add

Adding /Users/Developer/anystack: ✔
Restarting Porter: ✔
```

A new `porter.yml` has been created. This file contains all the services you want to run in the background, for example:

```yaml
services:
  - name: Queue
    command: php artisan horizon
    processes: 3 # Optional, number of parallel processes. Defaults to 1
    restart:
        watch:
            - app/Jobs
            - app/Mail/WelcomEmail.php
    
  - name: Vite
    directory: Users/developer/anystack/front-end
    command: npm run dev
    environment:
        FOO: "BAR"

  - name: Octane
    command: php artisan octane:start --port=8000 --no-interaction

  - name: Stripe
    command: stripe listen --forward-to localhost:8000/webhooks/stripe
    restart:
      minutes: 5
```

The following properties are available per command:

| Property    | Description                                                                          | Required |
|-------------|--------------------------------------------------------------------------------------|----------|
| name        | Shortname that describes your service.                                               | Yes      |
| directory   | Set the working directory, defaults to porter.yml directory.                         | No  |
| command     | The command to run relative to the root of your project or custom defined directory. | Yes      |
| restart     |                                                                                      |          | 
| - minutes   | After how many minutes the service should restart.                                   | No       | 
| - watch     | Restart service if files or directories are modified.                                | No       | 
| processes   | Set the number of parallel processes for the service. Defaults to 1.                 | No  |
| environment | Set custom environment variables                                                     | No  |

If you have made changes to your `porter.yml` you can use the `porter restart` command to apply your changes.

### Monitoring services
To monitor your services you can use the `porter status` command.

```shell
~/Developer/anystack: $ porter status
+----------+-----------------+---------+---------------------------+
| App      | Name            | Status  | Description               |
+----------+-----------------+---------+---------------------------+
| anystack | anystack-octane | RUNNING | pid 41277, uptime 0:03:29 |
| anystack | anystack-queue  | RUNNING | pid 41275, uptime 0:03:29 |
| anystack | anystack-vite   | RUNNING | pid 41276, uptime 0:03:29 |
+----------+-----------------+---------+---------------------------+
```

### Tail service logs

#### Basic tail usage
You can tail one or more services (unified) using the `porter tail` command.

This command is context-aware and will automatically ask which services you want to tail:

```shell
~/Developer/anystack: $ porter tail

 Which service do you want to tail?:
  [0] anystack-octane
  [1] anystack-queue
  [2] anystack-vite
 > 0,1
 
 Use CTRL+C to stop tailing.
 
 Horizon started successfully.
 
 INFO  Server running…
 Local: http://127.0.0.1:8000
 200    GET / ... 33.38 mb 79.10 ms
 ```

#### Tail all available services

To automatically tail all available services, pass the `--all` option:

```shell
~/Developer/anystack: $ porter tail --all
 Use CTRL+C to stop tailing.
 
 Horizon started successfully.
 
 INFO  Server running…
 Local: http://127.0.0.1:8000
 200    GET / ... 33.38 mb 79.10 ms
```

#### Tail one or more services

You can specify one or more services that you would like to tail by passing  
the `--services` option with a comma-separated list of service indexes or service names.

You can find the index and name of each available service by running `porter tail` with no arguments:

```shell
~/Developer/anystack: $ porter tail

 Which service do you want to tail?:
  [0] anystack-octane
  [1] anystack-queue
  [2] anystack-vite
```

The following examples reference the service names and indexes found above:

```shell
~/Developer/anystack: $ porter tail --services=0,2
```

```shell
~/Developer/anystack: $ porter tail --services=anystack-octane,anystack-vite
```

_The above two commands are functionally equivalent._

```shell
~/Developer/anystack: $ porter tail --services=1
```

```shell
~/Developer/anystack: $ porter tail --services=anystack-queue
```

_The above two commands are functionally equivalent._

### All available commands

| Command          | Description                                 |
|------------------|---------------------------------------------|
| `porter add`     | Add current directory as a new application. |
| `porter remove`  | Remove current application services.        |
| `porter start`   | Start all services.                         |
| `porter restart` | Restart one or multiple services.           |
| `porter stop`    | Stop all services.                          |
| `porter tail`    | Tail service logs.                          |

### Brought to you by Anystack
Anystack is the all-in-one product platform that helps you make a living by writing code. Push your code to GitHub, and we will take care of everything else.  [Start your adventure today](https://anystack.sh?utm_source=github&utm_campaign=porter&utm_medium=repository). 

## License
Porter is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
