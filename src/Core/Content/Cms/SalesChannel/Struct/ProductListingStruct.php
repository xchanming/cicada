<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\SalesChannel\Struct;

use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('buyers-experience')]
class ProductListingStruct extends Struct
{
    /**
     * @var EntitySearchResult<ProductCollection>|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $listing;

    /**
     * @return EntitySearchResult<ProductCollection>|null
     */
    public function getListing(): ?EntitySearchResult
    {
        return $this->listing;
    }

    /**
     * @param EntitySearchResult<ProductCollection> $listing
     */
    public function setListing(EntitySearchResult $listing): void
    {
        $this->listing = $listing;
    }

    public function getApiAlias(): string
    {
        return 'cms_product_listing';
    }
}
