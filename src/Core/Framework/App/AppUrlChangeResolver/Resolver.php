<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\AppUrlChangeResolver;

use Cicada\Core\Framework\App\Exception\AppUrlChangeStrategyNotFoundException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class Resolver
{
    /**
     * @param AbstractAppUrlChangeStrategy[] $strategies
     */
    public function __construct(private readonly iterable $strategies)
    {
    }

    public function resolve(string $strategyName, Context $context): void
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->getName() === $strategyName) {
                $strategy->resolve($context);

                return;
            }
        }

        throw new AppUrlChangeStrategyNotFoundException($strategyName);
    }

    /**
     * @return array<string>
     */
    public function getAvailableStrategies(): array
    {
        $strategies = [];

        foreach ($this->strategies as $strategy) {
            $strategies[$strategy->getName()] = $strategy->getDescription();
        }

        return $strategies;
    }
}
