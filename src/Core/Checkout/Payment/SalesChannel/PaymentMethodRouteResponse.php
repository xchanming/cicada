<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\SalesChannel;

use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

/**
 * @method EntitySearchResult<PaymentMethodCollection> getObject()
 */
#[Package('checkout')]
class PaymentMethodRouteResponse extends StoreApiResponse
{
    /**
     * @var EntitySearchResult<PaymentMethodCollection>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    /**
     * @param EntitySearchResult<PaymentMethodCollection> $paymentMethods
     */
    public function __construct(EntitySearchResult $paymentMethods)
    {
        parent::__construct($paymentMethods);
    }

    public function getPaymentMethods(): PaymentMethodCollection
    {
        return $this->object->getEntities();
    }
}
