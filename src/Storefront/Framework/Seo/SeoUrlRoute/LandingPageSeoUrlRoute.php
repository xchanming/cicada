<?php declare(strict_types=1);

namespace Cicada\Storefront\Framework\Seo\SeoUrlRoute;

use Cicada\Core\Content\LandingPage\LandingPageDefinition;
use Cicada\Core\Content\LandingPage\LandingPageEntity;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlMapping;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteConfig;
use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;

#[Package('inventory')]
class LandingPageSeoUrlRoute implements SeoUrlRouteInterface
{
    final public const ROUTE_NAME = 'frontend.landing.page';
    final public const DEFAULT_TEMPLATE = '{{ landingPage.translated.url }}';

    /**
     * @internal
     */
    public function __construct(private readonly LandingPageDefinition $landingPageDefinition)
    {
    }

    public function getConfig(): SeoUrlRouteConfig
    {
        return new SeoUrlRouteConfig(
            $this->landingPageDefinition,
            self::ROUTE_NAME,
            self::DEFAULT_TEMPLATE,
            true
        );
    }

    public function prepareCriteria(Criteria $criteria, SalesChannelEntity $salesChannel): void
    {
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsFilter('salesChannels.id', $salesChannel->getId()));
    }

    public function getMapping(Entity $landingPage, ?SalesChannelEntity $salesChannel): SeoUrlMapping
    {
        if (!$landingPage instanceof LandingPageEntity) {
            throw new \InvalidArgumentException('Expected LandingPageEntity');
        }

        $landingPageJson = $landingPage->jsonSerialize();

        return new SeoUrlMapping(
            $landingPage,
            ['landingPageId' => $landingPage->getId()],
            [
                'landingPage' => $landingPageJson,
            ]
        );
    }
}
