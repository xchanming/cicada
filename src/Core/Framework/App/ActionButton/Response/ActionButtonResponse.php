<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\ActionButton\Response;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
abstract class ActionButtonResponse extends Struct
{
    public function __construct(protected string $actionType)
    {
    }
}
