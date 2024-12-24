<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Validation\Error;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
abstract class Error extends \Exception
{
    abstract public function getMessageKey(): string;
}
