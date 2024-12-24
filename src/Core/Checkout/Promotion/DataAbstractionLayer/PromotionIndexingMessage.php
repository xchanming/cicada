<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\DataAbstractionLayer;

use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
class PromotionIndexingMessage extends EntityIndexingMessage
{
}
