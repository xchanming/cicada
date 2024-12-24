<?php declare(strict_types=1);

namespace Cicada\Core\System\Snippet\Struct;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @extends Collection<MissingSnippetStruct>
 */
#[Package('services-settings')]
class MissingSnippetCollection extends Collection
{
}
