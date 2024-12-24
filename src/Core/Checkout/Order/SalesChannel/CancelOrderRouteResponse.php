<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

#[Package('checkout')]
class CancelOrderRouteResponse extends StoreApiResponse
{
    /**
     * @var StateMachineStateEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(StateMachineStateEntity $object)
    {
        parent::__construct($object);
    }

    public function getState(): StateMachineStateEntity
    {
        return $this->object;
    }
}
