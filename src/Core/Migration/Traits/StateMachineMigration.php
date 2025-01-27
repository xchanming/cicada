<?php declare(strict_types=1);

namespace Cicada\Core\Migration\Traits;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('framework')]
class StateMachineMigration
{
    public function __construct(
        private string $technicalName,
        private string $zh,
        private string $en,
        private array $states = [],
        private array $transitions = [],
        private ?string $initialState = null
    ) {
    }

    public static function state(string $technicalName, string $zh, string $en): array
    {
        return ['technicalName' => $technicalName, 'zh' => $zh, 'en' => $en];
    }

    public static function transition(string $actionName, string $from, string $to): array
    {
        return ['actionName' => $actionName, 'from' => $from, 'to' => $to];
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function setTechnicalName(string $technicalName): void
    {
        $this->technicalName = $technicalName;
    }

    public function getZh(): string
    {
        return $this->zh;
    }

    public function setZh(string $zh): void
    {
        $this->zh = $zh;
    }

    public function getEn(): string
    {
        return $this->en;
    }

    public function setEn(string $en): void
    {
        $this->en = $en;
    }

    public function getStates(): array
    {
        return $this->states;
    }

    public function setStates(array $states): void
    {
        $this->states = $states;
    }

    public function getTransitions(): array
    {
        return $this->transitions;
    }

    public function setTransitions(array $transitions): void
    {
        $this->transitions = $transitions;
    }

    public function getInitialState(): ?string
    {
        return $this->initialState;
    }

    public function setInitialState(?string $initialState): void
    {
        $this->initialState = $initialState;
    }
}
