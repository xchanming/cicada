<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

/**
 * @internal
 */
#[Package('core')]
class GenericStoreApiResponse extends StoreApiResponse
{
    public function __construct(
        int $code,
        Struct $object
    ) {
        $this->setStatusCode($code);

        parent::__construct($object);
    }
}
