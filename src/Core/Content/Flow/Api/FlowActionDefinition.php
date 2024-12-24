<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Api;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('services-settings')]
class FlowActionDefinition extends Struct
{
    /**
     * @param array<string> $requirements
     */
    public function __construct(
        protected string $name,
        protected array $requirements,
        protected bool $delayable = false
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array<string>
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    /**
     * @param array<string> $requirements
     */
    public function setRequirements(array $requirements): void
    {
        $this->requirements = $requirements;
    }

    public function setDelayable(bool $delayable): void
    {
        $this->delayable = $delayable;
    }

    public function getDelayable(): bool
    {
        return $this->delayable;
    }
}
