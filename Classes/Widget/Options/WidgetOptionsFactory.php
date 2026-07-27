<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\Options;

use MoveElevator\Typo3Toolbox\Widget\EndOfLife\TimeWindow;

/**
 * Builds typed, validated option objects from the raw option arrays that the
 * dashboard passes to each widget.
 *
 * All validation errors are raised as {@see InvalidWidgetOptionsException} and
 * carry the exact config path of the offending value.
 */
final class WidgetOptionsFactory
{
    private const int DEFAULT_RECENT_EDITS_LIMIT = 8;
    private const array DEFAULT_EXCLUDED_TABLES = [
        'sys_file_reference',
        'sys_file_metadata',
        'sys_history',
        'sys_log',
        'sys_refindex',
    ];
    private const int DEFAULT_WARNING_THRESHOLD_DAYS = 180;
    private const string DEFAULT_WINDOW_FROM = '-1 year';
    private const string DEFAULT_WINDOW_TO = '+4 years';

    /**
     * @param array<string, mixed> $options
     */
    public function createRecentEditsOptions(array $options): RecentEditsOptions
    {
        $reader = new OptionsReader($options);

        return new RecentEditsOptions(
            limit: max(1, $reader->int('limit', self::DEFAULT_RECENT_EDITS_LIMIT)),
            allowedTables: $reader->stringList('allowedTables'),
            excludedTables: $reader->stringList('excludedTables', self::DEFAULT_EXCLUDED_TABLES),
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createQuickActionsOptions(array $options): QuickActionsOptions
    {
        $reader = new OptionsReader($options);

        $actions = [];
        foreach ($reader->children('actions') as $action) {
            $actions[] = $this->createQuickAction($action);
        }

        return new QuickActionsOptions($actions);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createEndOfLifeOptions(array $options, \DateTimeImmutable $now): EndOfLifeOptions
    {
        $reader = new OptionsReader($options);

        $components = [];
        foreach ($reader->children('components') as $component) {
            $components[] = new ComponentRequest(
                product: $component->requireString('product'),
                version: $component->requireString('version'),
                eltsContract: $component->bool('eltsContract', false),
                label: $component->string('label'),
            );
        }

        $window = $reader->child('timeWindow');

        return new EndOfLifeOptions(
            components: $components,
            timeWindow: new TimeWindow(
                $this->resolveDate($now, $window, 'from', self::DEFAULT_WINDOW_FROM),
                $this->resolveDate($now, $window, 'to', self::DEFAULT_WINDOW_TO),
            ),
            warningThresholdDays: max(0, $reader->int('warningThresholdDays', self::DEFAULT_WARNING_THRESHOLD_DAYS)),
        );
    }

    private function createQuickAction(OptionsReader $action): QuickAction
    {
        $present = $action->present(['url', 'module', 'record']);
        if (count($present) !== 1) {
            $action->failSelf('an action requires exactly one of "url", "module" or "record"');
        }

        $type = QuickActionType::from($present[0]);
        $label = $action->requireString('label');
        $iconIdentifier = $action->string('icon');
        $beGroups = $action->stringList('beGroups');

        return match ($type) {
            QuickActionType::Url => new QuickAction(
                $type,
                $label,
                $iconIdentifier,
                $action->requireString('url'),
                null,
                [],
                null,
                null,
                $beGroups,
            ),
            QuickActionType::Module => new QuickAction(
                $type,
                $label,
                $iconIdentifier,
                null,
                $action->requireString('module'),
                $action->stringMap('params'),
                null,
                null,
                $beGroups,
            ),
            QuickActionType::Record => $this->createRecordAction($action, $label, $iconIdentifier, $beGroups),
        };
    }

    /**
     * @param list<string> $beGroups
     */
    private function createRecordAction(
        OptionsReader $action,
        string $label,
        ?string $iconIdentifier,
        array $beGroups,
    ): QuickAction {
        $record = $action->child('record');

        return new QuickAction(
            QuickActionType::Record,
            $label,
            $iconIdentifier,
            null,
            null,
            [],
            $record->requireString('table'),
            $record->int('pid', 0),
            $beGroups,
        );
    }

    private function resolveDate(
        \DateTimeImmutable $now,
        OptionsReader $window,
        string $key,
        string $default,
    ): \DateTimeImmutable {
        $spec = $window->string($key, $default) ?? $default;

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $spec) === 1) {
            $absolute = \DateTimeImmutable::createFromFormat('!Y-m-d', $spec);
            if ($absolute === false || $absolute->format('Y-m-d') !== $spec) {
                $window->fail($key, sprintf('"%s" is not a valid date', $spec));
            }

            return $absolute;
        }

        try {
            return $now->modify($spec);
        } catch (\DateMalformedStringException) {
            $window->fail($key, sprintf('"%s" is not a valid relative date', $spec));
        }
    }
}
