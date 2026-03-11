<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\{Configuration, NamedFilter};

return static function (Configuration $config): Configuration {
    $config->addNamedFilter(NamedFilter::fromString('networkteam/sentry-client'));
    $config->addNamedFilter(NamedFilter::fromString('typo3/cms-linkvalidator'));

    return $config;
};
