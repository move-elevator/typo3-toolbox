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
    /**
     * @param list<ComponentLifecycle> $components
     * @return array{
     *     bars: list<array{label: string, version: string, leadingPercent: float, segments: list<array{type: string, widthPercent: float, start: ?\DateTimeImmutable, end: ?\DateTimeImmutable}>, badge: ?array{kind: string, date: ?\DateTimeImmutable}}>,
     *     todayPercent: ?float,
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
            'todayPercent' => $window->contains($now) ? round($window->offsetPercentOf($now), 4) : null,
            'years' => $this->years($window),
        ];
    }

    /**
     * @return array{label: string, version: string, leadingPercent: float, segments: list<array{type: string, widthPercent: float, start: ?\DateTimeImmutable, end: ?\DateTimeImmutable}>, badge: ?array{kind: string, date: ?\DateTimeImmutable}}
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
                'widthPercent' => round($width, 4),
                'start' => $phase->start,
                'end' => $phase->end,
            ];
        }

        return [
            'label' => $component->label,
            'version' => $component->version,
            'leadingPercent' => round($window->offsetPercentOf($component->firstPhaseStart() ?? $window->start), 4),
            'segments' => $segments,
            'badge' => $this->badge($component, $now, $warningDays),
        ];
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
