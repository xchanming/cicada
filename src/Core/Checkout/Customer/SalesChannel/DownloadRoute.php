<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItemDownload\OrderLineItemDownloadEntity;
use Cicada\Core\Content\Media\File\DownloadResponseGenerator;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('checkout')]
class DownloadRoute extends AbstractDownloadRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $downloadRepository,
        private readonly DownloadResponseGenerator $downloadResponseGenerator
    ) {
    }

    public function getDecorated(): AbstractDownloadRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/order/download/{orderId}/{downloadId}', name: 'store-api.account.order.single.download', methods: ['GET'], defaults: ['_loginRequired' => true, '_loginRequiredAllowGuest' => true])]
    public function load(Request $request, SalesChannelContext $context): Response
    {
        $customer = $context->getCustomer();
        $downloadId = $request->get('downloadId', false);
        $orderId = $request->get('orderId', false);

        if (!$customer) {
            throw CustomerException::customerNotLoggedIn();
        }

        if ($downloadId === false || $orderId === false) {
            throw RoutingException::missingRequestParameter(!$downloadId ? 'downloadId' : 'orderId');
        }

        $criteria = new Criteria([$downloadId]);
        $criteria->addAssociation('media');
        $criteria->addFilter(new MultiFilter(
            MultiFilter::CONNECTION_AND,
            [
                new EqualsFilter('orderLineItem.order.id', $orderId),
                new EqualsFilter('orderLineItem.order.orderCustomer.customerId', $customer->getId()),
                new EqualsFilter('accessGranted', true),
            ]
        ));

        $download = $this->downloadRepository->search($criteria, $context->getContext())->first();

        if (!$download instanceof OrderLineItemDownloadEntity || !$download->getMedia()) {
            throw CustomerException::downloadFileNotFound($downloadId);
        }

        $media = $download->getMedia();

        return $this->downloadResponseGenerator->getResponse($media, $context);
    }
}
