<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\FindVariant;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('inventory')]
class FindProductVariantRouteResponse extends StoreApiResponse
{
    /**
     * @var FoundCombination
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(FoundCombination $object)
    {
        parent::__construct($object);
    }

    public function getFoundCombination(): FoundCombination
    {
        return $this->object;
    }
}
