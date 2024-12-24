<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Order;

use Cicada\Core\Content\Product\Aggregate\ProductDownload\ProductDownloadCollection;
use Cicada\Core\Content\Product\State;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class LineItemDownloadLoader
{
    /**
     * @internal
     */
    public function __construct(private readonly EntityRepository $productDownloadRepository)
    {
    }

    /**
     * @param mixed[][] $lineItems
     *
     * @return array<int, array<int, array{position: int, mediaId: string, accessGranted: bool}>>
     */
    public function load(array $lineItems, Context $context): array
    {
        $lineItemKeys = [];
        foreach ($lineItems as $key => $lineItem) {
            $productId = $lineItem['referencedId'] ?? null;
            $states = $lineItem['states'] ?? null;

            if (
                !$productId
                || !$states
                || !\in_array(State::IS_DOWNLOAD, $states, true)
                || !empty($lineItem['downloads'])
            ) {
                continue;
            }

            $lineItemKeys[(string) $productId] = (int) $key;
        }

        if (empty($lineItemKeys)) {
            return [];
        }

        return $this->getLineItemDownloadPayload($lineItemKeys, $context);
    }

    /**
     * @param array<string, int> $lineItemKeys
     *
     * @return array<int, array<int, array{position: int, mediaId: string, accessGranted: bool}>>
     */
    private function getLineItemDownloadPayload(array $lineItemKeys, Context $context): array
    {
        $productIds = array_keys($lineItemKeys);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('productId', $productIds));

        $context = clone $context;
        $context->assign(['versionId' => Defaults::LIVE_VERSION]);

        /** @var ProductDownloadCollection $productDownloads */
        $productDownloads = $this->productDownloadRepository->search($criteria, $context)->getEntities();

        $downloads = [];
        foreach ($productDownloads->getElements() as $productDownload) {
            $key = $lineItemKeys[$productDownload->getProductId()] ?? null;

            if ($key === null) {
                continue;
            }

            $downloads[$key][] = [
                'position' => $productDownload->getPosition(),
                'mediaId' => $productDownload->getMediaId(),
                'accessGranted' => false,
            ];
        }

        return $downloads;
    }
}
