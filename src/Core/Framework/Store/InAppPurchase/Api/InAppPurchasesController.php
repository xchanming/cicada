<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\InAppPurchase\Api;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\InAppPurchase;
use Cicada\Core\Framework\Store\StoreException;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('checkout')]
class InAppPurchasesController
{
    /**
     * @param EntityRepository<AppCollection> $appRepository
     */
    public function __construct(
        private readonly InAppPurchase $inAppPurchase,
        private readonly EntityRepository $appRepository,
    ) {
    }

    #[Route(path: '/api/store/active-in-app-purchases', name: 'api.store.active-in-app-purchases', methods: ['GET'])]
    public function activeExtensionInAppPurchases(Context $context): JsonResponse
    {
        return new JsonResponse(
            ['inAppPurchases' => $this->inAppPurchase->getByExtension($this->getAppName($context))]
        );
    }

    #[Route(path: '/api/store/check-in-app-purchase-active', name: 'api.store.check-in-app-purchase-active', methods: ['POST'])]
    public function checkExtensionInAppPurchaseIsActive(RequestDataBag $request, Context $context): JsonResponse
    {
        $identifier = \trim($request->getString('identifier'));
        if (!$identifier) {
            throw StoreException::missingRequestParameter('identifier');
        }

        return new JsonResponse(
            ['isActive' => $this->inAppPurchase->isActive($this->getAppName($context), $identifier)]
        );
    }

    private function getAppName(Context $context): string
    {
        $source = $context->getSource();

        if (!$source instanceof AdminApiSource) {
            throw StoreException::invalidContextSource(AdminApiSource::class, $source::class);
        }

        if ($source->getIntegrationId() === null) {
            throw StoreException::missingIntegrationInContextSource($source::class);
        }

        $appId = $source->getIntegrationId();
        $app = $this->appRepository->search(new Criteria([$appId]), $context)->getEntities()->first();
        if (!$app) {
            throw StoreException::extensionNotFoundFromId($appId);
        }

        return $app->getName();
    }
}
