<div align="center">

![Extension icon](Resources/Public/Icons/Extension.svg)

# TYPO3 extension `typo3_toolbox`

[![Supported TYPO3 versions](https://badgen.net/badge/TYPO3/13/orange)]()
[![License](https://poser.pugx.org/move-elevator/typo3-toolbox/license)](LICENSE.md)
[![last commit](https://img.shields.io/github/last-commit/move-elevator/typo3-toolbox)](https://github.com/move-elevator/typo3-toolbox/commits)

</div>

This extension provides several tools for TYPO3 integrators and developers.

## Features:
- Adds an event listener to minify HTML output
- Adds an event listener to add save and close button
- Adds a xClass for TYPO3 asset collector which will automatically render `noscript` tags beside CSS link tags, which can be adopted to optimize CSS preloading (see: https://web.dev/articles/defer-non-critical-css)
- Adds a view helper which can return the uid of the first content element on a page X
- Adds a CSS view helper that enables the rendering of a `noscript` variant and allows inline styles to be replaced by a key-value-based `inlineReplacements` option flag
- Adds a sentry middleware and frontend module ...
- Adds a custom TYPO3 page renderer template which removes some unnecessary spaces and changes the order of inline CSS injection

## Requirements

- PHP 8.4
- TYPO3 13.4

## Installation

### Composer

``` bash
composer require move-elevator/typo3-toolbox
```

## Configuration

### Sentry

Add the following environment variables to your `.env` file to configure Sentry:

```dotenv
SENTRY_DSN=''
SENTRY_ENVIRONMENT=''
SENTRY_RELEASE=''
```

If you want to use the Sentry frontend monitoring as well, you can use the shipped Sentry Monitoring Service JavaScript or just adopt this.

For example:

```
<f:asset.script
    defer="1"
    identifier="sentryMonitoringService"
    nonce="{f:security.nonce()}"
    priority="1"
    src="EXT:typo3_toolbox/Resources/Public/JavaScript/Service/SentryMonitoringService.min.js"
/>
```

Sentry monitoring is enabled by default for frontend and backend issue/ performance tracking, but can be disabled via the extension configuration if required.

Disable backend issue tracking:

```
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_toolbox']['sentryBackendEnabled'] = 0;
```

Disable frontend issue/ performance tracking:

```
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['typo3_toolbox']['sentryFrontendEnabled'] = 0;
```

## Documentation

### Content Minifier

The `ContentMinifierEventListener` automatically minifies the HTML output of all cacheable frontend pages. It hooks into the TYPO3 `AfterCacheableContentIsGeneratedEvent` and is active by default — no configuration required.

#### Optimizations

| Optimization                     | Description                                                                                                                                                                                                   |
|----------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Remove JS inline comments        | Strips `/** */` comments                                                                                                                                                                                      |
| Collapse whitespace              | Converts linebreaks, tabs, and multiple spaces into single spaces                                                                                                                                             |
| Remove inter-tag spaces          | Removes spaces between HTML tags (preserves inline tags: `a`, `b`, `strong`, `img`, `em`, `i`, `span`, `small`, `big`)                                                                                        |
| Fix self-closing tags            | Converts `" />` to `">` for HTML5 conformity                                                                                                                                                                  |
| Remove redundant type attributes | Strips `type="text/css"` from `<style>` and `type="text/javascript"` from `<script>` tags                                                                                                                     |
| Normalize class attributes       | Collapses multiple spaces within `class` attribute values                                                                                                                                                     |
| Minify JSON-LD schemas           | Re-encodes `<script type="application/ld+json">` content as compact JSON; removes invalid schemas                                                                                                             |
| Remove CKEditor data attributes  | Strips `data-list-item-id` attributes from `<li>` elements added by CKEditor 5 ([TYPO3#109002](https://forge.typo3.org/issues/109002), [CKEditor5#19006](https://github.com/ckeditor/ckeditor5/issues/19006)) |
| Trim tag content whitespace      | Removes leading/trailing whitespace inside `h1`–`h6`, `p`, `li`, `td`, `th`, `dt`, `dd`, `button`, and `label` tags                                                                                           |

### Middlewares

| Middleware            | Path/ Parameter   | Description                                                                |
|-----------------------|-------------------|----------------------------------------------------------------------------|
| SentryMiddleware      | /api/sentry       | Returns sentry environment data as json which is consumed in the frontend. |

### TypoScript

The extension ships a site set (`Toolbox`) that includes the following TypoScript configuration:

- **Admin Panel** (`Config.typoscript`): Enables the TYPO3 admin panel and sets the custom page renderer template.

### Page TSconfig

The site set also provides default Page TSconfig via `page.tsconfig`:

- **TCEMAIN** (`TCEMAIN.tsconfig`): Configures default user/group permissions and table-specific copy behavior for `pages` and `tt_content` (disables prepending "[Translate to...]" on copy, keeps copied elements visible).
- **Clipboard** (`Mod.tsconfig`): Enables the clipboard in the web list module.
- **Link Validator** (`Extensions/LinkValidator.tsconfig`): Enables validation for `db`, `file` and `external` link types and sets a 10-second timeout for external link validation.

### User TSconfig

The extension provides a default `user.tsconfig` that configures the admin panel modules:

- Enabled: `cache`, `edit`, `preview`
- Disabled: `debug`, `info`, `publish`, `tsdebug`

## License

This project is licensed
under [GNU General Public License 2.0 (or later)](LICENSE.md).
