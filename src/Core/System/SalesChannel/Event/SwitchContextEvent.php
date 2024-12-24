<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('core')]
class SwitchContextEvent implements CicadaSalesChannelEvent
{
    public const CONSISTENT_CHECK = self::class . '.consistent_check';
    public const DATABASE_CHECK = self::class . '.database_check';

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        private RequestDataBag $requestData,
        private SalesChannelContext $salesChannelContext,
        private DataValidationDefinition $dataValidationDefinition,
        private array $parameters,
    ) {
    }

    public function getRequestData(): RequestDataBag
    {
        return $this->requestData;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getDataValidationDefinition(): DataValidationDefinition
    {
        return $this->dataValidationDefinition;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
