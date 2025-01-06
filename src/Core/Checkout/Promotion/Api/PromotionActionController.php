<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Api;

use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupPackagerInterface;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupServiceRegistry;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupSorterInterface;
use Cicada\Core\Checkout\Promotion\Cart\Discount\Filter\FilterServiceRegistry;
use Cicada\Core\Framework\Log\Package;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('checkout')]
class PromotionActionController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly LineItemGroupServiceRegistry $serviceRegistry,
        private readonly FilterServiceRegistry $filterServiceRegistry
    ) {
    }

    #[Route(path: '/api/_action/promotion/setgroup/packager', name: 'api.action.promotion.setgroup.packager', methods: ['GET'], defaults: ['_acl' => ['promotion:read']])]
    public function getSetGroupPackagers(): JsonResponse
    {
        $packagerKeys = [];

        /** @var LineItemGroupPackagerInterface $packager */
        foreach ($this->serviceRegistry->getPackagers() as $packager) {
            $packagerKeys[] = $packager->getKey();
        }

        return new JsonResponse($packagerKeys);
    }

    #[Route(path: '/api/_action/promotion/setgroup/sorter', name: 'api.action.promotion.setgroup.sorter', methods: ['GET'], defaults: ['_acl' => ['promotion:read']])]
    public function getSetGroupSorters(): JsonResponse
    {
        $sorterKeys = [];

        /** @var LineItemGroupSorterInterface $sorter */
        foreach ($this->serviceRegistry->getSorters() as $sorter) {
            $sorterKeys[] = $sorter->getKey();
        }

        return new JsonResponse($sorterKeys);
    }

    #[Route(path: '/api/_action/promotion/discount/picker', name: 'api.action.promotion.discount.picker', methods: ['GET'], defaults: ['_acl' => ['promotion:read']])]
    public function getDiscountFilterPickers(): JsonResponse
    {
        $pickerKeys = [];

        foreach ($this->filterServiceRegistry->getPickers() as $picker) {
            $pickerKeys[] = $picker->getKey();
        }

        return new JsonResponse($pickerKeys);
    }
}
