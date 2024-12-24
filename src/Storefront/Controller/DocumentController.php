<?php declare(strict_types=1);

namespace Cicada\Storefront\Controller;

use Cicada\Core\Checkout\Document\SalesChannel\AbstractDocumentRoute;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 * Do not use direct or indirect repository calls in a controller. Always use a store-api route to get or put data
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
#[Package('storefront')]
class DocumentController extends StorefrontController
{
    /**
     * @internal
     */
    public function __construct(private readonly AbstractDocumentRoute $documentRoute)
    {
    }

    #[Route(path: '/account/order/document/{documentId}/{deepLinkCode}', name: 'frontend.account.order.single.document', defaults: ['_loginRequired' => true, '_loginRequiredAllowGuest' => true], methods: ['GET'])]
    public function downloadDocument(Request $request, SalesChannelContext $context, string $documentId): Response
    {
        return $this->documentRoute->download($documentId, $request, $context, $request->get('deepLinkCode'));
    }
}
