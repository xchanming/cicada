<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Processing\Pipe;

use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
abstract class AbstractPipe
{
    abstract public function in(Config $config, iterable $record): iterable;

    abstract public function out(Config $config, iterable $record): iterable;

    protected function getDecorated(): AbstractPipe
    {
        throw new \RuntimeException('Implement getDecorated');
    }
}
