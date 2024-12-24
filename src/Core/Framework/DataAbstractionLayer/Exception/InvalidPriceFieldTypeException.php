<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Exception;

use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class InvalidPriceFieldTypeException extends CicadaHttpException
{
    public function __construct(string $type)
    {
        parent::__construct(
            'The price field does not contain a valid "type" value. Received {{ type }} ',
            ['type' => $type]
        );
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__INVALID_PRICE_FIELD_TYPE';
    }
}
