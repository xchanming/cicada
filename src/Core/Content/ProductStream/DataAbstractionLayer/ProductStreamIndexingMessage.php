<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductStream\DataAbstractionLayer;

use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class ProductStreamIndexingMessage extends EntityIndexingMessage
{
}
