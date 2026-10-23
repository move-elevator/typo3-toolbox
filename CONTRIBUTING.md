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
ddev cgl lint

# Specific linters
ddev cgl  lint:composer
ddev cgl  lint:editorconfig
ddev cgl  lint:typoscript
ddev cgl  lint:php
ddev cgl  sca
ddev cgl  migration
```

## Commit messages

Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/). Allowed types are `chore`, `docs`, `feat`, `fix`, `refactor` and `test`, e.g. `feat: add sentry dashboard widget`. A `commit-msg` hook installed by `ddev start` validates every message.

## Create a release

Releases are prepared with [version-bumper](https://github.com/eliashaeussler/version-bumper). It derives the next version from the Conventional Commits since the last tag, writes it to `extra.typo3/cms.version` in `composer.json` (shown as extension version in TYPO3), updates `composer.lock` and creates a release commit. `main` is protected, so the release commit goes through a pull request:

```bash
git switch -c release/x.y.z origin/main

# Preview the next version
ddev composer bump-version --dry-run --release

# Create the release commit
ddev composer bump-version --release
git push -u origin release/x.y.z
```

Pass a range (`major`, `minor`, `patch`) or an explicit version to override the detected one, e.g. `ddev composer bump-version 3.0.0 --release`.

After the pull request is merged, tag the release commit on `main`, e.g. with a GitHub release (tag `x.y.z`, target `main`). version-bumper does not create the tag: "Rebase and merge" gives the commit a new hash, so a tag created on the release branch would not point to a commit on `main`.

The version lives in `extra` on purpose: Composer and Packagist take the version from the Git tag and ignore this field.

Commits created inside DDEV use the Git identity of the container. To commit with your own name, add a `.gitconfig` with your `[user]` section to `~/.ddev/homeadditions/` and run `ddev restart`.

## Submit a pull request

Thank you for your contribution!

Once your changes are ready, please **open a pull request** and describe what you have changed. Ideally, your PR should reference an issue describing the problem you are solving.

All mentioned code quality tools will be run automatically on each pull request.
