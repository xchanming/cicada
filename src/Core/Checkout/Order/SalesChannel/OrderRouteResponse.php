<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\SalesChannel;

use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayStruct;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('checkout')]
class OrderRouteResponse extends StoreApiResponse
{
    /**
     * @var EntitySearchResult<OrderCollection>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    /**
     * @var array<string, bool>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $paymentChangeable = [];

    public function getObject(): Struct
    {
        return new ArrayStruct([
            'orders' => $this->object,
            'paymentChangeable' => $this->paymentChangeable,
        ], 'order-route-response-struct');
    }

    /**
     * @return EntitySearchResult<OrderCollection>
     */
    public function getOrders(): EntitySearchResult
    {
        return $this->object;
    }

    /**
     * @return array<string, bool>
     */
    public function getPaymentsChangeable(): array
    {
        return $this->paymentChangeable;
    }

    /**
     * @param array<string, bool> $paymentChangeable
     */
    public function setPaymentChangeable(array $paymentChangeable): void
    {
        $this->paymentChangeable = $paymentChangeable;
    }

    /**
     * @param array<string, bool> $paymentChangeable
     */
    public function addPaymentChangeable(array $paymentChangeable): void
    {
        $this->paymentChangeable = array_merge($this->paymentChangeable, $paymentChangeable);
    }
}
