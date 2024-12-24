<?php declare(strict_types=1);

namespace Cicada\Core\Content\LandingPage\Aggregate\LandingPageTranslation;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<LandingPageTranslationEntity>
 */
#[Package('buyers-experience')]
class LandingPageTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return LandingPageTranslationEntity::class;
    }
}
