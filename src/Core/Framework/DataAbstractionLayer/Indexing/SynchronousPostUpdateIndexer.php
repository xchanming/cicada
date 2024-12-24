<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Indexing;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
abstract class SynchronousPostUpdateIndexer extends PostUpdateIndexer
{
}
