<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Exception;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class AppUrlChangeStrategyNotFoundException extends \RuntimeException
{
    public function __construct(string $strategyName)
    {
        parent::__construct('Unable to find AppUrlChangeResolver with name: "' . $strategyName . '".');
    }
}
