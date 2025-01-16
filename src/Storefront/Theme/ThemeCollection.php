<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<ThemeEntity>
 */
#[Package('framework')]
class ThemeCollection extends EntityCollection
{
    public function getByTechnicalName(string $technicalName): ?ThemeEntity
    {
        return $this->filter(fn (ThemeEntity $theme) => $theme->getTechnicalName() === $technicalName)->first();
    }

    protected function getExpectedClass(): string
    {
        return ThemeEntity::class;
    }
}
