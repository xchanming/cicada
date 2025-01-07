<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Core\Strategy;

use Cicada\Core\Content\Media\Core\Application\AbstractMediaPathStrategy;
use Cicada\Core\Content\Media\MediaException;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal Factory is only used for DI container construction to find configured strategy
 */
#[Package('discovery')]
class PathStrategyFactory
{
    /**
     * @internal
     *
     * @param AbstractMediaPathStrategy[] $strategies
     */
    public function __construct(private readonly iterable $strategies)
    {
    }

    public function factory(string $strategyName): AbstractMediaPathStrategy
    {
        return $this->findStrategyByName($strategyName);
    }

    /**
     * @throws MediaException
     */
    private function findStrategyByName(string $strategyName): AbstractMediaPathStrategy
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->name() === $strategyName) {
                return $strategy;
            }
        }

        throw MediaException::strategyNotFound($strategyName);
    }
}
