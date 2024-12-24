<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer;

use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
abstract class BulkEntityExtension
{
    /**
     * Constructor is final to ensure the extensions can be built without any dependencies
     */
    final public function __construct()
    {
    }

    /**
     * @return \Generator<string, list<Field>>
     */
    abstract public function collect(): \Generator;
}
