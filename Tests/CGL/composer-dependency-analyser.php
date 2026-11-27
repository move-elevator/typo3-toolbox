<?php

declare(strict_types=1);

use Composer\Autoload;
use ShipMonk\ComposerDependencyAnalyser;

$rootPath = dirname(__DIR__, 2);

/** @var Autoload\ClassLoader $loader */
$loader = require $rootPath.'/vendor/autoload.php';
$loader->register();

$configuration = new ComposerDependencyAnalyser\Config\Configuration();
$configuration
    ->addPathToScan($rootPath.'/Configuration', false)
    ->addPathToScan($rootPath.'/ext_localconf.php', false)
    ->addPathsToExclude([
        $rootPath.'/Tests/CGL',
    ])
    // Required for the projects that install the toolbox, nothing in this repository uses them
    ->ignoreErrorsOnPackages([
        'typo3/cms-adminpanel',
        'typo3/cms-linkvalidator',
    ], [ComposerDependencyAnalyser\Config\ErrorType::UNUSED_DEPENDENCY])
;

return $configuration;
