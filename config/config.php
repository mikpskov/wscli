<?php

declare(strict_types=1);

return [
    'cache' => [
        'path' => BASE_PATH . '/cache',
    ],
    'worksnaps' => [
        'token' => '',
        'project' => '',
        'user' => '',
    ],
    'calendar' => [
        // in format: <day> => <hours>
        // example: '2020-09-21' => 0
        'vacations' => [
        ],
    ],
];
