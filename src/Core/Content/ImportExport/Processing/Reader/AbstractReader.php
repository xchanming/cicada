<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Processing\Reader;

use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
abstract class AbstractReader
{
    /**
     * @param resource $resource
     */
    abstract public function read(Config $config, $resource, int $offset): iterable;

    abstract public function getOffset(): int;

    protected function getDecorated(): AbstractReader
    {
        throw new \RuntimeException('Implement getDecorated');
    }
}
