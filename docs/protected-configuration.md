# Protect ENV-backed settings from persistence

Apply the optional TYPO3 14.3 Core patch to prevent selected secrets from being
written to `config/system/settings.php`. TYPO3 can otherwise persist values from
`additional.php` when synchronizing extension configuration. This can expose
ENV-backed credentials to version control or tools allowed to read `settings.php`.

The patch filters the array immediately before serialization in
`ConfigurationManager::writeLocalConfiguration()`. It does not need the Toolbox
to be booted, a compiled container or an event listener, so it also covers writes
through this method in the Install Tool's failsafe mode.

## Apply the patch in the consuming project

Installing the Toolbox alone does **not** activate this protection. Configure the
patch in the root TYPO3 project's Composer setup. The Toolbox does not install a
patch plugin or modify its own installed Core automatically.

These instructions use [Composer Patches 2.x](https://docs.cweagans.net/composer-patches/).
If the project already has a patch mechanism, register the supplied patch there.

1. Enable and install the plugin in the project root:

   ```bash
   composer config allow-plugins.cweagans/composer-patches true
   composer require 'cweagans/composer-patches:^2.0'
   ```

2. Copy the patch into the project's versioned patch directory:

   ```bash
   mkdir -p patches
   cp vendor/move-elevator/typo3-toolbox/patches/typo3-cms-core-protected-configuration.patch patches/
   ```

   The command assumes the default Composer vendor directory. Keeping a project
   copy makes the patch available before dependencies are installed on a fresh
   deployment. Toolbox updates do not automatically update that copy.

3. Merge this definition into the root `composer.json`, preserving existing
   `extra` entries and patches:

   ```json
   {
     "extra": {
       "patches": {
         "typo3/cms-core": {
           "Keep protected configuration values out of settings.php": "patches/typo3-cms-core-protected-configuration.patch"
         }
       }
     }
   }
   ```

4. Lock and apply the patch:

   ```bash
   composer patches-relock
   composer patches-repatch
   composer update --lock
   ```

   `patches-repatch` reinstalls patched dependencies. Commit the project patch,
   `composer.json`, `composer.lock` and `patches.lock.json`. Keep Composer plugins
   enabled during deployments so the patch is applied on fresh installations.

## Configure protected paths

Set the following custom option in `config/system/additional.php`, after any
other configuration merges. Do not register it in `ext_localconf.php`: the
configuration must also be available in failsafe mode.

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['typo3_toolbox']['protectedConfigurationPaths'] = [
    'EXTENSIONS/typo3_image_compression/apiKey',
    'MAIL/transport_smtp_password',
    'DB/Connections/Default/password',
];

$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_image_compression']['apiKey']
    = (string) getenv('TINY_IMG_API_KEY');
```

The option is a list of exact slash-separated paths. There are no default paths,
wildcards or automatic secret detection. Protected values, including `null`, are
written as the placeholder `<set by dotenv>`. Missing paths are not created and
other values are preserved. Malformed lists or entries abort the write with a
`SettingsWriteException`, without exposing their values or changing the existing
file. A path that points to a whole section is left unchanged.

Paths that already contain `<set by dotenv>` in `settings.php` are protected as
well, even without a list entry. Prefer the list anyway: it also repairs a value
that was persisted before the patch was active, and it covers paths that a Core
upgrade wizard moves to a new key. A placeholder path that is removed from the
configuration stays removed.

If an ENV variable is missing at runtime, TYPO3 sees the placeholder as the value.
Search `$GLOBALS['TYPO3_CONF_VARS']` for `<set by dotenv>` to detect this.

Only list individual credential fields whose runtime values are supplied by
`additional.php` or another reliable external configuration source. Do not list
entire configuration sections. In the example, also provide the database and
SMTP credentials through the project's existing ENV overrides.

The actual ENV values remain available to TYPO3 at runtime. Credentials entered
only through the backend for protected paths will not survive the next request.

## Verify activation

Use dummy credentials on a development instance to trigger an extension
configuration write through **Admin Tools > Settings**. Confirm that the
protected fields in `settings.php` contain `<set by dotenv>`, other changes are saved and the
ENV-backed credentials still work at runtime. Do not print real secret values.

Remove any previously persisted credentials from `settings.php` once during
rollout. The patch affects future writes; applying it alone does not rewrite an
existing file. Treat credentials already exposed in Git or agent transcripts as
compromised and rotate them.

## Core updates and limitations

The patch targets TYPO3 14.3 and must be checked after Core updates. Run these
tests in a Toolbox development checkout with an **unpatched** Core dependency,
not against the already-patched dependency in a consuming project:

```bash
composer test:unit -- Tests/Unit/Configuration/ProtectedConfigurationPatchTest.php
```

The tests apply the shipped patch to a temporary copy of the installed Core and
exercise real writes, including extension configuration through a failsafe
container. Git and PHP's `proc_open()` must be available to run these tests.
The installed Core is not modified by the tests.

Protection covers writes through `ConfigurationManager`. It does not intercept
extensions writing files directly, other configuration files, logs, database
storage or runtime inspection.
