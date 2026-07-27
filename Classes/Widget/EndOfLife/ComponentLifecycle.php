<?php

declare(strict_types=1);

namespace MoveElevator\Typo3Toolbox\Widget\EndOfLife;

/**
 * The resolved lifecycle of a single component, ready to be laid out on the axis.
 */
final readonly class ComponentLifecycle
{
    /**
     * @param list<LifecyclePhase> $phases ordered full support → security → ELTS
     */
    public function __construct(
        public string $product,
        public string $version,
        public string $label,
        public array $phases,
        public bool $eltsContract,
        public ?\DateTimeImmutable $securityEnd,
        public ?\DateTimeImmutable $eltsEnd,
    ) {
    }

    public function firstPhaseStart(): ?\DateTimeImmutable
    {
        return $this->phases === [] ? null : $this->phases[0]->start;
    }

    /**
     * True while "today" sits inside the ELTS phase (security support ended, ELTS not yet over).
     */
    public function isInElts(\DateTimeImmutable $now): bool
    {
        return $this->securityEnd !== null
            && $this->eltsEnd !== null
            && $now >= $this->securityEnd
            && $now <= $this->eltsEnd;
    }

    public function eltsRequired(\DateTimeImmutable $now): bool
    {
        return $this->isInElts($now) && !$this->eltsContract;
    }

    public function eltsActive(\DateTimeImmutable $now): bool
    {
        return $this->isInElts($now) && $this->eltsContract;
    }

    /**
     * True when free security support has not ended yet but will within the given days.
     */
    public function securityEndsSoon(\DateTimeImmutable $now, int $days): bool
    {
        if ($this->securityEnd === null || $now >= $this->securityEnd) {
            return false;
        }

        return ($this->securityEnd->getTimestamp() - $now->getTimestamp()) <= $days * 86400;
    }
}
