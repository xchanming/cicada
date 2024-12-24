<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
interface StorageAware
{
    public function getStorageName(): string;
}
