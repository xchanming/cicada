<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Aggregate\ProductManufacturerTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ProductManufacturerTranslationEntity>
 */
#[Package('inventory')]
class ProductManufacturerTranslationCollection extends EntityCollection
{
    /**
     * @return array<string>
     */
    public function getProductManufacturerIds(): array
    {
        return $this->fmap(fn (ProductManufacturerTranslationEntity $productManufacturerTranslation) => $productManufacturerTranslation->getProductManufacturerId());
    }

    public function filterByProductManufacturerId(string $id): self
    {
        return $this->filter(fn (ProductManufacturerTranslationEntity $productManufacturerTranslation) => $productManufacturerTranslation->getProductManufacturerId() === $id);
    }

    /**
     * @return array<string>
     */
    public function getLanguageIds(): array
    {
        return $this->fmap(fn (ProductManufacturerTranslationEntity $productManufacturerTranslation) => $productManufacturerTranslation->getLanguageId());
    }

    public function filterByLanguageId(string $id): self
    {
        return $this->filter(fn (ProductManufacturerTranslationEntity $productManufacturerTranslation) => $productManufacturerTranslation->getLanguageId() === $id);
    }

    public function getApiAlias(): string
    {
        return 'product_manufacturer_translation_collection';
    }

    protected function getExpectedClass(): string
    {
        return ProductManufacturerTranslationEntity::class;
    }
}
