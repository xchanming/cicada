<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme\Aggregate;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ThemeTranslationEntity>
 */
#[Package('storefront')]
class ThemeTranslationCollection extends EntityCollection
{
    /**
     * @return array<string>
     */
    public function getThemeIds(): array
    {
        return $this->fmap(fn (ThemeTranslationEntity $themeTranslation) => $themeTranslation->getThemeId());
    }

    public function filterByThemeId(string $id): self
    {
        return $this->filter(fn (ThemeTranslationEntity $themeTranslation) => $themeTranslation->getThemeId() === $id);
    }

    /**
     * @return array<string>
     */
    public function getLanguageIds(): array
    {
        return $this->fmap(fn (ThemeTranslationEntity $themeTranslation) => $themeTranslation->getLanguageId());
    }

    public function filterByLanguageId(string $id): self
    {
        return $this->filter(fn (ThemeTranslationEntity $themeTranslation) => $themeTranslation->getLanguageId() === $id);
    }

    protected function getExpectedClass(): string
    {
        return ThemeTranslationEntity::class;
    }
}
