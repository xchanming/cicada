<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\Framework\Struct\VariablesAccessTrait;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class StoreApiResponse extends Response
{
    // allows the cache key finder to get access of all returned data to build the cache tags
    use VariablesAccessTrait;

    /**
     * @var Struct
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(Struct $object)
    {
        parent::__construct();
        $this->object = $object;
    }

    public function getObject(): Struct
    {
        return $this->object;
    }
}
