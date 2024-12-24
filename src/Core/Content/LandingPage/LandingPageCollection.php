<?php declare(strict_types=1);

namespace Cicada\Core\Content\LandingPage;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<LandingPageEntity>
 */
#[Package('buyers-experience')]
class LandingPageCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return LandingPageEntity::class;
    }
}
