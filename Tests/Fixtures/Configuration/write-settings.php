<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\Exception\SettingsWriteException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DependencyInjection\FailsafeContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

require $argv[1] . '/autoload.php';
require $argv[2] . '/Classes/Configuration/ConfigurationManager.php';

$input = json_decode($argv[3], true, flags: JSON_THROW_ON_ERROR);
$root = $argv[2];
mkdir($root . '/config/system', 0775, true);
mkdir($root . '/public', 0775, true);
$initialContents = '<?php return ' . var_export($input['initialSettings'], true) . ';';
file_put_contents($root . '/config/system/settings.php', $initialContents);

Environment::initialize(
    new ApplicationContext('Development'),
    true,
    true,
    $root,
    $root . '/public',
    $root . '/var',
    $root . '/config',
    __FILE__,
    'UNIX',
);

$GLOBALS['TYPO3_CONF_VARS'] = array_replace_recursive(
    require $argv[1] . '/typo3/cms-core/Configuration/DefaultConfiguration.php',
    $input['configuration'],
);
if ($input['protectedPaths'] !== null) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['typo3_toolbox']['protectedConfigurationPaths']
        = $input['protectedPaths'];
}
$runtimeConfiguration = $GLOBALS['TYPO3_CONF_VARS'];

// Use the same early instance arrangement as the Install Tool's failsafe boot.
GeneralUtility::setContainer(new FailsafeContainer([], [
    ConfigurationManager::class => new ConfigurationManager(),
]));
try {
    if ($input['extensionWrite']) {
        new ExtensionConfiguration()->setAll($input['configuration']['EXTENSIONS']);
    } else {
        new ConfigurationManager()->writeLocalConfiguration($input['configuration']);
    }
} catch (SettingsWriteException $exception) {
    echo json_encode([
        'writeRejected' => true,
        'fileUnchanged' => file_get_contents($root . '/config/system/settings.php') === $initialContents,
        'runtimePreserved' => $runtimeConfiguration === $GLOBALS['TYPO3_CONF_VARS'],
    ], JSON_THROW_ON_ERROR);
    exit;
}

echo json_encode([
    'stored' => require $root . '/config/system/settings.php',
    'contents' => file_get_contents($root . '/config/system/settings.php'),
    'runtimePreserved' => $runtimeConfiguration === $GLOBALS['TYPO3_CONF_VARS'],
], JSON_THROW_ON_ERROR);
