<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\LandingPage;

use Cicada\Core\Content\LandingPage\LandingPageDefinition;
use Cicada\Core\Content\LandingPage\LandingPageEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Page\Page;

#[Package('buyers-experience')]
class LandingPage extends Page
{
    protected ?LandingPageEntity $landingPage = null;

    public function getEntityName(): string
    {
        return LandingPageDefinition::ENTITY_NAME;
    }

    public function getLandingPage(): ?LandingPageEntity
    {
        return $this->landingPage;
    }

    public function setLandingPage(?LandingPageEntity $landingPage): void
    {
        $this->landingPage = $landingPage;
    }
}
