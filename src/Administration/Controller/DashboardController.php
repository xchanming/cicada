<?php declare(strict_types=1);

namespace Cicada\Administration\Controller;

use Cicada\Administration\Dashboard\OrderAmountService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route(defaults: ['_routeScope' => ['administration']])]
#[Package('framework')]
class DashboardController extends AbstractController
{
    public function __construct(private readonly OrderAmountService $orderAmountService)
    {
    }

    #[Route(path: '/api/_admin/dashboard/order-amount/{since}', name: 'api.admin.dashboard.order-amount', defaults: ['_routeScope' => ['administration']], methods: ['GET'])]
    public function orderAmount(string $since, Request $request, Context $context): JsonResponse
    {
        $paid = $request->query->getBoolean('paid', true);

        $timezone = $request->query->get('timezone', 'Asia/Shanghai');

        $amount = $this->orderAmountService->load($context, $since, $paid, $timezone);

        return new JsonResponse(['statistic' => $amount]);
    }
}
