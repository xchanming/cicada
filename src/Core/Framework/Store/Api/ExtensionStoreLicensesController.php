<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Api;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Services\AbstractExtensionStoreLicensesService;
use Cicada\Core\Framework\Store\Struct\ReviewStruct;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route(defaults: ['_routeScope' => ['api'], '_acl' => ['system.plugin_maintain']])]
#[Package('checkout')]
class ExtensionStoreLicensesController extends AbstractController
{
    public function __construct(private readonly AbstractExtensionStoreLicensesService $extensionStoreLicensesService)
    {
    }

    #[Route(path: '/api/license/cancel/{licenseId}', name: 'api.license.cancel', methods: ['DELETE'])]
    public function cancelSubscription(int $licenseId, Context $context): JsonResponse
    {
        $this->extensionStoreLicensesService->cancelSubscription($licenseId, $context);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/api/license/rate/{extensionId}', name: 'api.license.rate', methods: ['POST'])]
    public function rateLicensedExtension(int $extensionId, Request $request, Context $context): JsonResponse
    {
        $this->extensionStoreLicensesService->rateLicensedExtension(
            ReviewStruct::fromRequest($extensionId, $request),
            $context
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
