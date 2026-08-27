<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\EndOfLife;

/**
 * Builds the render-ready view model for the End-of-Life timeline.
 *
 * All geometry (segment widths, leading offsets, the "today" marker and the
 * year ticks) is computed here in PHP so the template stays pure Fluid + CSS.
 */
final class TimelineFactory
{
    private const string DATE_FORMAT = 'Y-m-d';
    private const string UNSUPPORTED = 'unsupported';

    /**
     * Rounding leftovers must not produce a hairline tail segment.
     */
    private const float MIN_TAIL_PERCENT = 0.1;

    /**
     * @param list<ComponentLifecycle> $components
     * @return array{
     *     bars: list<array{label: string, version: string, leadingPercent: float, segments: list<array{type: string, cssModifier: string, widthPercent: float, start: ?\DateTimeImmutable, end: ?\DateTimeImmutable, startLabel: ?string, endLabel: ?string}>, badge: ?array{kind: string, date: ?\DateTimeImmutable}}>,
     *     showToday: bool,
     *     todayPercent: float,
     *     years: list<array{year: int, percent: float}>
     * }
     */
    public function build(array $components, TimeWindow $window, \DateTimeImmutable $now, int $warningDays): array
    {
        $bars = [];
        foreach ($components as $component) {
            $bars[] = $this->buildBar($component, $window, $now, $warningDays);
        }

        return [
            'bars' => $bars,
            'showToday' => $window->contains($now),
            'todayPercent' => round($window->offsetPercentOf($now), 4),
            'years' => $this->years($window),
        ];
    }

    /**
     * @return array{label: string, version: string, leadingPercent: float, segments: list<array{type: string, cssModifier: string, widthPercent: float, start: ?\DateTimeImmutable, end: ?\DateTimeImmutable, startLabel: ?string, endLabel: ?string}>, badge: ?array{kind: string, date: ?\DateTimeImmutable}}
     */
    private function buildBar(ComponentLifecycle $component, TimeWindow $window, \DateTimeImmutable $now, int $warningDays): array
    {
        $segments = [];
        foreach ($component->phases as $phase) {
            $width = $phase->widthPercentOf($window);
            if ($width <= 0.0) {
                continue;
            }
            $segments[] = [
                'type' => $phase->type->value,
                'cssModifier' => $phase->type->cssModifier(),
                'widthPercent' => round($width, 4),
                'start' => $phase->start,
                'end' => $phase->end,
                'startLabel' => $this->dateLabel($phase->start),
                'endLabel' => $this->dateLabel($phase->end),
            ];
        }

        $leadingPercent = round($window->offsetPercentOf($component->firstPhaseStart() ?? $window->start), 4);

        return [
            'label' => $component->label,
            'version' => $component->version,
            'leadingPercent' => $leadingPercent,
            'segments' => $this->withUnsupportedTail($segments, $leadingPercent),
            'badge' => $this->badge($component, $now, $warningDays),
        ];
    }

    /**
     * Closes the bar with an explicit "no support" segment when the last phase
     * ends inside the window. Without it the bare track would show through and
     * read like just another lifecycle phase.
     *
     * @param list<array{type: string, cssModifier: string, widthPercent: float, start: ?\DateTimeImmutable, end: ?\DateTimeImmutable, startLabel: ?string, endLabel: ?string}> $segments
     * @return list<array{type: string, cssModifier: string, widthPercent: float, start: ?\DateTimeImmutable, end: ?\DateTimeImmutable, startLabel: ?string, endLabel: ?string}>
     */
    private function withUnsupportedTail(array $segments, float $leadingPercent): array
    {
        $used = array_sum(array_column($segments, 'widthPercent')) + $leadingPercent;
        $remaining = 100.0 - $used;
        if ($remaining <= self::MIN_TAIL_PERCENT) {
            return $segments;
        }

        $last = $segments === [] ? null : $segments[count($segments) - 1];
        $segments[] = [
            'type' => self::UNSUPPORTED,
            'cssModifier' => self::UNSUPPORTED,
            'widthPercent' => round($remaining, 4),
            'start' => $last['end'] ?? null,
            'end' => null,
            'startLabel' => $last['endLabel'] ?? null,
            'endLabel' => null,
        ];

        return $segments;
    }

    /**
     * @return array{kind: string, date: ?\DateTimeImmutable}|null
     */
    private function badge(ComponentLifecycle $component, \DateTimeImmutable $now, int $warningDays): ?array
    {
        if ($component->eltsRequired($now)) {
            return ['kind' => 'eltsRequired', 'date' => null];
        }
        if ($component->eltsActive($now)) {
            return ['kind' => 'eltsActive', 'date' => $component->eltsEnd];
        }
        if ($component->isEndOfLife($now)) {
            return ['kind' => 'endOfLife', 'date' => null];
        }
        if ($component->securityEndsSoon($now, $warningDays)) {
            return ['kind' => 'warning', 'date' => $component->securityEnd];
        }

        return null;
    }

    /**
     * Formats a phase boundary for display, or null when there is nothing
     * meaningful to show: an open boundary, or the epoch marker the data
     * provider uses for "reached at an unknown date".
     */
    private function dateLabel(?\DateTimeImmutable $date): ?string
    {
        if ($date === null || $date->getTimestamp() <= 0) {
            return null;
        }

        return $date->format(self::DATE_FORMAT);
    }

    /**
     * @return list<array{year: int, percent: float}>
     */
    private function years(TimeWindow $window): array
    {
        $ticks = [];
        $startYear = (int)$window->start->format('Y');
        $endYear = (int)$window->end->format('Y');

        for ($year = $startYear; $year <= $endYear; ++$year) {
            $tick = \DateTimeImmutable::createFromFormat('!Y-m-d', $year . '-01-01');
            if ($tick === false || !$window->contains($tick)) {
                continue;
            }
            $ticks[] = ['year' => $year, 'percent' => round($window->offsetPercentOf($tick), 4)];
        }

        return $ticks;
    }
}
