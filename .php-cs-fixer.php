<?php

declare(strict_types=1);

$finder = new PhpCsFixer\Finder()
    ->exclude('node_modules')
    ->ignoreVCSIgnored(true)
    ->in(realpath(__DIR__));

return new PhpCsFixer\Config()
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => true,
    ])
    ->setFinder($finder);
