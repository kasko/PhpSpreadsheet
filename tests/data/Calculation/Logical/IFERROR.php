<?php

return [
    [
        null,
        'Error',
        null,
    ],
    [
        true,
        'Error',
        true,
    ],
    [
        42,
        'Error',
        42,
    ],
    [
        '',
        'Error',
        '',
    ],
    [
        'ABC',
        'Error',
        'ABC',
    ],
    [
        '#VALUE!',
        'Error',
        'Error',
    ],
    [
        '#NAME?',
        'Error',
        'Error',
    ],
    [
        '#N/A',
        'Error',
        'Error',
    ],
];