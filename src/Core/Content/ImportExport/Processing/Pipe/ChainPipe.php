<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Processing\Pipe;

use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
class ChainPipe extends AbstractPipe
{
    /**
     * @param AbstractPipe[] $chain
     */
    public function __construct(private readonly array $chain)
    {
    }

    public function in(Config $config, iterable $record): iterable
    {
        $generator = $record;

        foreach ($this->chain as $pipe) {
            $generator = $pipe->in($config, $generator);
        }

        yield from $generator;
    }

    public function out(Config $config, iterable $record): iterable
    {
        $pipes = array_reverse($this->chain);

        $generator = $record;

        foreach ($pipes as $pipe) {
            $generator = $pipe->out($config, $generator);
        }

        yield from $generator;
    }
}
