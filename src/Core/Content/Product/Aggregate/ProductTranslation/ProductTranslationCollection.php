<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Aggregate\ProductTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ProductTranslationEntity>
 */
#[Package('inventory')]
class ProductTranslationCollection extends EntityCollection
{
    /**
     * @return array<string>
     */
    public function getProductIds(): array
    {
        return $this->fmap(fn (ProductTranslationEntity $productTranslation) => $productTranslation->getProductId());
    }

    public function filterByProductId(string $id): self
    {
        return $this->filter(fn (ProductTranslationEntity $productTranslation) => $productTranslation->getProductId() === $id);
    }

    /**
     * @return array<string>
     */
    public function getLanguageIds(): array
    {
        return $this->fmap(fn (ProductTranslationEntity $productTranslation) => $productTranslation->getLanguageId());
    }

    public function filterByLanguageId(string $id): self
    {
        return $this->filter(fn (ProductTranslationEntity $productTranslation) => $productTranslation->getLanguageId() === $id);
    }

    public function getApiAlias(): string
    {
        return 'product_translation_collection';
    }

    protected function getExpectedClass(): string
    {
        return ProductTranslationEntity::class;
    }
}
