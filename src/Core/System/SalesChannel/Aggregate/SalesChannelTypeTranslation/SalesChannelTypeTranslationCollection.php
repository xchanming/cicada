<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Aggregate\SalesChannelTypeTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<SalesChannelTypeTranslationEntity>
 */
#[Package('buyers-experience')]
class SalesChannelTypeTranslationCollection extends EntityCollection
{
    /**
     * @return array<string>
     */
    public function getSalesChannelTypeIds(): array
    {
        return $this->fmap(fn (SalesChannelTypeTranslationEntity $salesChannelTypeTranslation) => $salesChannelTypeTranslation->getSalesChannelTypeId());
    }

    public function filterBySalesChannelId(string $id): self
    {
        return $this->filter(fn (SalesChannelTypeTranslationEntity $salesChannelTypeTranslation) => $salesChannelTypeTranslation->getSalesChannelTypeId() === $id);
    }

    /**
     * @return array<string>
     */
    public function getLanguageIds(): array
    {
        return $this->fmap(fn (SalesChannelTypeTranslationEntity $salesChannelTranslation) => $salesChannelTranslation->getLanguageId());
    }

    public function filterByLanguageId(string $id): self
    {
        return $this->filter(fn (SalesChannelTypeTranslationEntity $salesChannelTranslation) => $salesChannelTranslation->getLanguageId() === $id);
    }

    public function getApiAlias(): string
    {
        return 'sales_channel_type_translation_collection';
    }

    protected function getExpectedClass(): string
    {
        return SalesChannelTypeTranslationEntity::class;
    }
}
