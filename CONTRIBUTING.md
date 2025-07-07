# Contributing

Thank you for your interest in contributing to this extension!

We use [DDEV](https://ddev.readthedocs.io/en/stable/) for development. Please set up DDEV before proceeding.

## Preparation

```bash
# Clone repository
git clone git@github.com:move-elevator/typo3-toolbox.git
cd typo3-toolbox

# Start DDEV project
ddev start

# Install dependencies
ddev composer install

# Setup TYPO3 with a prefilled database
ddev init-typo3
```

You can access the TYPO3 backend at <https://typo3-toolbox.ddev.site/typo3/>.

To login, you can use the username `admin` and password `Password1!`.

## Code analysis and linters

```bash
# All linters
composer lint

# Specific linters
composer lint:composer
composer lint:editorconfig
composer lint:typoscript
composer lint:php
composer lint:php:stan
composer lint:php:fixer
composer lint:php:rector
```

## Submit a pull request

Thank you for your contribution!

Once your changes are ready, please **open a pull request** and describe what you have changed. Ideally, your PR should reference an issue describing the problem you are solving.

All mentioned code quality tools will be run automatically on each pull request.