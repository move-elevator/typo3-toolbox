<?php

declare(strict_types=1);

return [
    'moveElevatorEditor' => [
        'title' => 'LLL:EXT:typo3_toolbox/Resources/Private/Language/locallang_be.xlf:dashboard.moveElevatorEditor',
        'description' => 'LLL:EXT:typo3_toolbox/Resources/Private/Language/locallang_be.xlf:dashboard.moveElevatorEditor.description',
        'iconIdentifier' => 'content-dashboard',
        'defaultWidgets' => [
            'typo3ToolboxWelcome',
            'typo3ToolboxRecentEdits',
            'typo3ToolboxQuickActions',
        ],
        'showInWizard' => true,
    ],
    'moveElevatorAdmin' => [
        'title' => 'LLL:EXT:typo3_toolbox/Resources/Private/Language/locallang_be.xlf:dashboard.moveElevatorAdmin',
        'description' => 'LLL:EXT:typo3_toolbox/Resources/Private/Language/locallang_be.xlf:dashboard.moveElevatorAdmin.description',
        'iconIdentifier' => 'content-dashboard',
        'defaultWidgets' => [
            'typo3ToolboxWelcome',
            'typo3ToolboxRecentEdits',
            'typo3ToolboxQuickActions',
            'typo3ToolboxEndOfLife',
            't3information',
            'sysLogErrors',
        ],
        'showInWizard' => true,
    ],
];
