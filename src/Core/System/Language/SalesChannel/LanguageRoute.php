<?php declare(strict_types=1);

namespace Cicada\Core\System\Language\SalesChannel;

use Cicada\Core\Framework\Adapter\Cache\Event\AddCacheTagEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('fundamentals@discovery')]
class LanguageRoute extends AbstractLanguageRoute
{
    final public const ALL_TAG = 'language-route';

    /**
     * @internal
     *
     * @param SalesChannelRepository<LanguageCollection> $repository
     */
    public function __construct(
        private readonly SalesChannelRepository $repository,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    public static function buildName(string $id): string
    {
        return 'language-route-' . $id;
    }

    public function getDecorated(): AbstractLanguageRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/language', name: 'store-api.language', methods: ['GET', 'POST'], defaults: ['_entity' => 'language'])]
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): LanguageRouteResponse
    {
        $this->dispatcher->dispatch(new AddCacheTagEvent(
            self::buildName($context->getSalesChannelId()),
            self::ALL_TAG
        ));

        $criteria->addAssociation('translationCode');

        return new LanguageRouteResponse(
            $this->repository->search($criteria, $context)
        );
    }
}
