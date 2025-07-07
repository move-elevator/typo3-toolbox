<?php

use MoveElevator\Typo3Toolbox\Middleware;

return [
    'frontend' => [
        'site/sentry' => [
            'target' => Middleware\SentryMiddleware::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
            ],
            'before' => [
                'typo3/cms-frontend/eid',
            ],
        ],
    ],
];
