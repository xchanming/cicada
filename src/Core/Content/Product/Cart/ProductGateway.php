<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Cart;

use Cicada\Core\Content\Product\Events\ProductGatewayCriteriaEvent;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('inventory')]
class ProductGateway implements ProductGatewayInterface
{
    /**
     * @internal
     *
     * @param SalesChannelRepository<ProductCollection> $repository
     */
    public function __construct(
        private readonly SalesChannelRepository $repository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @param list<string> $ids
     */
    public function get(array $ids, SalesChannelContext $context): ProductCollection
    {
        $criteria = new Criteria($ids);
        $criteria->setTitle('cart::products');
        $criteria->addAssociation('cover.media');
        $criteria->addAssociation('options.group');
        $criteria->addAssociation('featureSet');
        $criteria->addAssociation('properties.group');

        $this->eventDispatcher->dispatch(
            new ProductGatewayCriteriaEvent($ids, $criteria, $context)
        );

        return $this->repository->search($criteria, $context)->getEntities();
    }
}
