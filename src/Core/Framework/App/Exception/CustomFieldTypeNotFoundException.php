<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Exception;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class CustomFieldTypeNotFoundException extends \InvalidArgumentException
{
    public function __construct(
        string $type,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(\sprintf('CustomFieldType for XML-Element "%s" not found.', $type), $code, $previous);
    }
}
