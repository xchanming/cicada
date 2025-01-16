<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Product;

use Cicada\Core\Content\Cms\CmsPageEntity;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Page\Page;

#[Package('framework')]
class ProductPage extends Page
{
    /**
     * @deprecated tag:v6.7.0 - Property will be native typed
     *
     * @var SalesChannelProductEntity
     */
    protected $product;

    /**
     * @deprecated tag:v6.7.0 - Property will be native typed
     *
     * @var CmsPageEntity
     */
    protected $cmsPage;

    protected ?string $navigationId = null;

    public function getProduct(): SalesChannelProductEntity
    {
        return $this->product;
    }

    public function setProduct(SalesChannelProductEntity $product): void
    {
        $this->product = $product;
    }

    public function getCmsPage(): ?CmsPageEntity
    {
        return $this->cmsPage;
    }

    public function setCmsPage(CmsPageEntity $cmsPage): void
    {
        $this->cmsPage = $cmsPage;
    }

    public function getNavigationId(): ?string
    {
        return $this->navigationId;
    }

    public function setNavigationId(?string $navigationId): void
    {
        $this->navigationId = $navigationId;
    }

    public function getEntityName(): string
    {
        return ProductDefinition::ENTITY_NAME;
    }
}
