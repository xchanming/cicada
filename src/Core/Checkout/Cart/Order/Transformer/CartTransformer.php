<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Order\Transformer;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Context\AdminSalesChannelApiSource;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Json;
use Cicada\Core\Framework\Util\Random;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class CartTransformer
{
    /**
     * @return array<string, mixed>
     */
    public static function transform(Cart $cart, SalesChannelContext $context, string $stateId, bool $setOrderDate = true): array
    {
        $currency = $context->getCurrency();
        $userId = null;
        $source = $context->getContext()->getSource();

        if ($source instanceof AdminSalesChannelApiSource) {
            $originalContextSource = $source->getOriginalContext()->getSource();
            if ($originalContextSource instanceof AdminApiSource) {
                $userId = $originalContextSource->getUserId();
            }
        }

        $data = [
            'price' => $cart->getPrice(),
            'shippingCosts' => $cart->getShippingCosts(),
            'stateId' => $stateId,
            'currencyId' => $currency->getId(),
            'currencyFactor' => $currency->getFactor(),
            'salesChannelId' => $context->getSalesChannel()->getId(),
            'lineItems' => [],
            'deliveries' => [],
            'deepLinkCode' => Random::getBase64UrlString(32),
            'customerComment' => $cart->getCustomerComment(),
            'affiliateCode' => $cart->getAffiliateCode(),
            'campaignCode' => $cart->getCampaignCode(),
            'source' => $cart->getSource(),
            'createdById' => $userId,
        ];

        if ($setOrderDate) {
            $data['orderDateTime'] = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        }

        $data['itemRounding'] = json_decode(Json::encode($context->getItemRounding()), true, 512, \JSON_THROW_ON_ERROR);
        $data['totalRounding'] = json_decode(Json::encode($context->getTotalRounding()), true, 512, \JSON_THROW_ON_ERROR);

        return $data;
    }
}
