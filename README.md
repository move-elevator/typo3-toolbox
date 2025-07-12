<div align="center">

# TYPO3 extension `typo3_toolbox`

[![Supported TYPO3 versions](https://badgen.net/badge/TYPO3/13/orange)]()
[![License](https://poser.pugx.org/move-elevator/typo3-toolbox/license)](LICENSE.md)
[![last commit](https://img.shields.io/github/last-commit/move-elevator/typo3-toolbox)](https://github.com/move-elevator/typo3-toolbox/commits)

</div>

This extension provides several tools for TYPO3 integrators and developers.

## Features:
- Adds an event listener to minify HTML output
- Adds an event listener to add save & close button
- Adds a xClass for TYPO3 asset collector which will automatically render `noscript` tags beside CSS link tags, which can be adopted to optimize CSS preloading (see: https://web.dev/articles/defer-non-critical-css)
- Adds a view helper which can return the uid of the first content element on a page X
- Adds a sentry middleware and frontend module ...

## Requirements

* TYPO3 13.4 & PHP 8.4

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
    src="EXT:typo3-toolbox/Resources/Public/JavaScript/Service/SentryMonitoringService.min.js"
/>
```

## Documentation

### Middlewares

| Middleware            | Path/ Parameter   | Description                                                                |
|-----------------------|-------------------|----------------------------------------------------------------------------|
| SentryMiddleware      | /api/sentry       | Returns sentry environment data as json which is consumed in the frontend. |


## License

This project is licensed
under [GNU General Public License 2.0 (or later)](LICENSE.md).