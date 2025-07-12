<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

return new Configuration()
    ->addPathToScan(__DIR__ . '/ext_localconf.php', isDev: false);
