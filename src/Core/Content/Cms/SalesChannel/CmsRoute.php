<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\SalesChannel;

use Cicada\Core\Content\Cms\Exception\PageNotFoundException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('buyers-experience')]
class CmsRoute extends AbstractCmsRoute
{
    /**
     * @internal
     */
    public function __construct(private readonly SalesChannelCmsPageLoaderInterface $cmsPageLoader)
    {
    }

    public function getDecorated(): AbstractCmsRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/cms/{id}', name: 'store-api.cms.detail', methods: ['GET', 'POST'])]
    public function load(string $id, Request $request, SalesChannelContext $context): CmsRouteResponse
    {
        $criteria = new Criteria([$id]);

        $slots = $request->get('slots');

        if (\is_string($slots)) {
            $slots = explode('|', $slots);
        }

        if (!empty($slots)) {
            $criteria
                ->getAssociation('sections.blocks')
                ->addFilter(new EqualsAnyFilter('slots.id', $slots));
        }

        $pages = $this->cmsPageLoader->load($request, $criteria, $context);

        if (!$pages->has($id)) {
            throw new PageNotFoundException($id);
        }

        return new CmsRouteResponse($pages->get($id));
    }
}
