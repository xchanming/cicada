<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Indexing;

use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
abstract class SynchronousPostUpdateIndexer extends PostUpdateIndexer
{
}
