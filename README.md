<div align="center">

![Extension icon](Resources/Public/Icons/Extension.svg)

# TYPO3 extension `typo3_toolbox`

[![Supported TYPO3 versions](https://badgen.net/badge/TYPO3/14/orange)]()
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
- Adds a backend avatar provider that assigns the move elevator logo to backend users with an `@move-elevator.de` email address (when no custom avatar is set)
- Adds four backend dashboard widgets (Welcome, Recent Edits, Quick Actions, End-of-Life) in a dedicated *move:elevator* widget group, plus `moveElevatorEditor` and `moveElevatorAdmin` dashboard presets

## Version support

| Extension version | TYPO3 | PHP      |
|-------------------|-------|----------|
| 2.x               | 14.3  | 8.4, 8.5 |
| 1.x               | 13.4  | 8.4, 8.5 |

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

### Backend Avatar

The `MoveElevatorAvatarProvider` automatically assigns the move elevator logo (`Resources/Public/Icons/me.svg`) as the backend avatar for any backend user whose email address ends with `@move-elevator.de`.

Personal avatars uploaded via the user settings always take precedence — the logo is only used as a fallback when no custom avatar is configured.

### Dashboard Widgets

The extension ships four backend dashboard widgets, grouped under the
*move:elevator* widget group. Two ready-made dashboard presets are provided:
`moveElevatorEditor` (Welcome + Recent Edits + Quick Actions) and
`moveElevatorAdmin` (all four widgets plus the core `t3information` and
`sysLogErrors` widgets).

Widget options are configured at registration time. To adjust them for your
project, re-declare the widget service in your own `Configuration/Services.yaml`
and pass an `$options` array — the snippets below show the available options.

Every label, title and intro text accepts either a plain string or an `LLL:`
reference, so a project can localize its dashboard without changing code.

> Screenshots: _to be captured from a running instance._

#### Welcome (`typo3ToolboxWelcome`)

The personalized entry point of a project dashboard: a time-of-day greeting for
the current backend user (real name, falling back to the username), an optional
intro text, and any number of typed cards. Renders with zero configuration.

| Option            | Type       | Default                | Description                                                        |
|-------------------|------------|------------------------|--------------------------------------------------------------------|
| `emoji`           | `string`   | `👋`                   | Shown before the greeting. Set to an empty string to omit it.       |
| `intro`           | `string`   | –                      | Intro paragraph below the greeting.                                 |
| `branding.enabled`| `bool`     | `true`                 | Whether the footer logo and claim are shown.                        |
| `branding.logo`   | `string`   | move:elevator logo     | `EXT:` resource path, or any URL/path used as-is.                    |
| `branding.claim`  | `string`   | `move:elevator`        | Text next to the logo.                                              |
| `branding.url`    | `string`   | `https://www.move-elevator.de/` | Link target of logo and claim, opened in a new tab. Set to an empty string to render the claim as plain text. |
| `cards`           | `array`    | `[]`                   | Cards to render, see below.                                         |

Each card has a `type` and an optional `title`:

- **`contact`** — a contact person: `name` (required), `role`, `image`, and
  `channels` of type `email`, `phone` or `mobile`. The `mailto:`/`tel:` href is
  built from the value, so a number can be written the way it should be read
  (`+49 170 12 34 56` → `tel:+49170123456`).
- **`links`** — a link collection; each link takes `label` plus **either** `url`
  **or** a backend `module` route (with optional `params`) and an optional `icon`.
  Links whose module route does not exist are skipped rather than rendered dead.
- **`custom`** — raw HTML for anything the typed cards do not cover. It is
  rendered unescaped, so only ever feed it deployed configuration, never user
  input.

Misconfiguration fails fast with the exact config path, e.g.
`cards.0.channels.1.type: unknown channel type "fax"`.

```yaml
services:
  MoveElevator\Typo3Toolbox\Widget\WelcomeWidget:
    arguments:
      $options:
        emoji: '🚀'
        intro: 'LLL:EXT:my_sitepackage/Resources/Private/Language/be.xlf:dashboard.intro'
        branding: { logo: 'EXT:my_sitepackage/Resources/Public/Icons/logo.svg', claim: 'Acme Corp', url: 'https://acme.example' }
        cards:
          - type: contact
            title: 'Your contact'
            name: 'Jane Doe'
            role: 'Project lead'
            image: 'EXT:my_sitepackage/Resources/Public/Images/jane.jpg'
            channels:
              - { type: email, value: 'jane@example.com' }
              - { type: mobile, value: '+49 170 12 34 56' }
          - type: links
            title: 'Handy links'
            links:
              - { label: 'Style guide', url: 'https://example.com/styleguide', icon: 'actions-book-open' }
              - { label: 'List view', module: 'records', params: { id: 1 } }
          - type: custom
            html: '<p>Deployment freeze until Monday.</p>'
    tags:
      - name: dashboard.widget
        identifier: typo3ToolboxWelcome
        groupNames: 'moveElevator'
        title: 'LLL:EXT:typo3_toolbox/Resources/Private/Language/locallang_be.xlf:widgets.welcome.title'
        description: 'LLL:EXT:typo3_toolbox/Resources/Private/Language/locallang_be.xlf:widgets.welcome.description'
        iconIdentifier: 'actions-heart'
        height: 'medium'
        width: 'medium'
```

#### Recent Edits (`typo3ToolboxRecentEdits`)

Lists **only the current user's own** most recent changes (read from
`sys_history`, grouped per record), each linking straight back into its edit
form. Changes made by other editors never appear — the widget answers "where was
I?", not "what happened on the site?". That is what sets it apart from the core
`latestChangedPages` widget, which reports every editor's changes and rolls
content elements up to their page; this one shows the record itself, across all
TCA tables. Records the user can no longer access — unknown TCA tables, deleted
records and tables failing the `tables_modify` check — are skipped. The widget
is intentionally uncached so it feels live right after an edit.

The user-scoped nature is surfaced in the UI as well: the widget is titled *My
Recent Edits* and carries an "Only your own changes" caption above the list.

| Option           | Type       | Default                                                                                          | Description                                        |
|------------------|------------|--------------------------------------------------------------------------------------------------|----------------------------------------------------|
| `limit`          | `int`      | `8`                                                                                              | Maximum number of records shown.                   |
| `allowedTables`  | `string[]` | `[]` (all)                                                                                       | If set, only these tables are shown.               |
| `excludedTables` | `string[]` | `sys_file_reference`, `sys_file_metadata`, `sys_history`, `sys_log`, `sys_refindex`              | Tables never shown (technical tables by default).  |

```yaml
services:
  MoveElevator\Typo3Toolbox\Widget\RecentEditsWidget:
    arguments:
      $options:
        limit: 12
        excludedTables: ['sys_file_reference', 'sys_log']
    tags:
      - name: dashboard.widget
        identifier: typo3ToolboxRecentEdits
        groupNames: 'moveElevator'
        title: 'LLL:EXT:typo3_toolbox/Resources/Private/Language/locallang_be.xlf:widgets.recentEdits.title'
        description: 'LLL:EXT:typo3_toolbox/Resources/Private/Language/locallang_be.xlf:widgets.recentEdits.description'
        iconIdentifier: 'actions-history'
        height: 'medium'
        width: 'medium'
```

#### Quick Actions (`typo3ToolboxQuickActions`)

A configurable shortcut list for the recurring editor workflows of a project.
Each action is exactly one of three types:

- **`url`** — an external link
- **`module`** — a backend module route (with optional `params`)
- **`record`** — `{ table, pid }`, opens the "create new record" form on that page

An optional `beGroups` filter hides an action for users outside the listed
backend groups (admins always see everything). Actions referencing unknown
tables or unresolvable module routes are skipped silently. Misconfiguration
fails fast at render time with the exact config path, e.g.
`actions.2: an action requires exactly one of "url", "module" or "record"`.

```yaml
services:
  MoveElevator\Typo3Toolbox\Widget\QuickActionsWidget:
    arguments:
      $options:
        actions:
          - { label: 'New article', icon: 'actions-plus', record: { table: 'tx_news_domain_model_news', pid: 42 } }
          - { label: 'Media', module: 'media_management' }
          - { label: 'List view', module: 'web_list', params: { id: 1 } }
          - { label: 'Style guide', url: 'https://example.com/styleguide', beGroups: ['2'] }
    tags:
      - name: dashboard.widget
        identifier: typo3ToolboxQuickActions
        groupNames: 'moveElevator'
        title: 'LLL:EXT:typo3_toolbox/Resources/Private/Language/locallang_be.xlf:widgets.quickActions.title'
        description: 'LLL:EXT:typo3_toolbox/Resources/Private/Language/locallang_be.xlf:widgets.quickActions.description'
        iconIdentifier: 'actions-lightning'
        height: 'small'
        width: 'medium'
```

#### End-of-Life (`typo3ToolboxEndOfLife`)

An admin-facing lifecycle overview: one segmented timeline bar per component on
a shared time axis with a "today" marker, including TYPO3 ELTS awareness.
Lifecycle data is read from [endoflife.date](https://endoflife.date), cached for
24h; a stale copy is kept indefinitely as a fallback, so API outages never break
the dashboard. Hovering a segment reveals its phase and date range (e.g.
`Security support: 2027-04-30 – 2028-10-31`); an open end reads as `open`, a
boundary the API reports as reached without a date as `unknown`.

**TYPO3, PHP and the database are detected automatically.** The database comes
from the default Doctrine connection: MariaDB and MySQL are tracked per
`major.minor`, PostgreSQL per `major`; SQLite and unrecognized platforms are
skipped, since they have no support lifecycle worth warning about. A database
that cannot be reached simply contributes no bar.

Everything else — Node.js, Solr, … — has to be listed under `components`:

```yaml
components:
  - { product: 'nodejs', version: '22' }
```

The phase colors run green → amber → orange, followed by a hatched section once
support has run out. Extended support is deliberately *not* red: being in it is a
lifecycle state, not an error. Red is reserved for the badges that really do
demand action ("ELTS required", "End of life"). The final section is hatched
rather than a flat grey so it does not read as a fourth phase.

Note that "extended support" is the generic lifecycle phase as endoflife.date
reports it — MariaDB, for instance, genuinely has one until 2033. Only the
*badges* speak of ELTS, since that is TYPO3's own paid programme.

| Option                 | Type    | Default                    | Description                                                                    |
|------------------------|---------|----------------------------|--------------------------------------------------------------------------------|
| `components`           | `array` | `[]`                       | Additional components, each `{ product, version, eltsContract?, label? }`. Also overrides an auto-detected product of the same id. |
| `warningThresholdDays` | `int`   | `180`                      | Show an early warning when free security support ends within this many days.   |
| `timeWindow.from`      | `string`| `-1 year`                  | Axis start — relative (e.g. `-1 year`) or absolute (`YYYY-MM-DD`).             |
| `timeWindow.to`        | `string`| `+4 years`                 | Axis end — relative or absolute.                                              |

`product` is an [endoflife.date](https://endoflife.date) product id (e.g.
`typo3`, `php`, `nodejs`). Set `eltsContract: true` on a component to signal that
an ELTS contract exists: inside the ELTS phase this renders a neutral
"ELTS active until …" badge instead of the red "ELTS required" badge. Listing an
auto-detected product (`typo3`, `php`, or the database) explicitly overrides its
detected entry — e.g. to flag an ELTS contract, or to pin a database version that
detection gets wrong behind a proxy.

```yaml
services:
  MoveElevator\Typo3Toolbox\Widget\EndOfLifeWidget:
    arguments:
      $options:
        warningThresholdDays: 120
        timeWindow: { from: '-1 year', to: '+4 years' }
        components:
          - { product: 'typo3', version: '13', eltsContract: true }
          - { product: 'nodejs', version: '22' }
    tags:
      - name: dashboard.widget
        identifier: typo3ToolboxEndOfLife
        groupNames: 'moveElevator'
        title: 'LLL:EXT:typo3_toolbox/Resources/Private/Language/locallang_be.xlf:widgets.endOfLife.title'
        description: 'LLL:EXT:typo3_toolbox/Resources/Private/Language/locallang_be.xlf:widgets.endOfLife.description'
        iconIdentifier: 'content-widget-chart-bar'
        height: 'medium'
        width: 'large'
```

> **Permissions:** the End-of-Life widget has no hard permission check in code.
> Restrict it to administrators (or specific groups) via the *Allowed dashboard
> widgets* setting of the relevant backend user groups.

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
