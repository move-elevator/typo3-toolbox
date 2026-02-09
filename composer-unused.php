<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\{Configuration, NamedFilter};

return static function (Configuration $config): Configuration {
    $config->addNamedFilter(NamedFilter::fromString('networkteam/sentry-client'));

    return $config;
};
