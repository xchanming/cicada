<?php declare(strict_types=1);

namespace Cicada\Core\Content\Test\Flow\fixtures;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CustomerAware;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('services-settings')]
class CustomerAwareEvent implements CustomerAware, FlowEventAware
{
    public function __construct(
        protected string $customerId,
        protected ?Context $context = null
    ) {
    }

    public static function getAvailableData(): EventDataCollection
    {
        return new EventDataCollection();
    }

    public function getName(): string
    {
        return 'customer.aware.event';
    }

    public function getContext(): Context
    {
        return $this->context ?? Context::createDefaultContext();
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }
}
