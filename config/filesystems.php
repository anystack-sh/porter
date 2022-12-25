<?php

return [
    'default' => 'local',
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => getenv('HOME').DIRECTORY_SEPARATOR.'.porter',
        ],
    ],
];
