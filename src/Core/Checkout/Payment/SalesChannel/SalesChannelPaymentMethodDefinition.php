<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\SalesChannel;

use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelDefinitionInterface;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class SalesChannelPaymentMethodDefinition extends PaymentMethodDefinition implements SalesChannelDefinitionInterface
{
    public function processCriteria(Criteria $criteria, SalesChannelContext $context): void
    {
        $criteria->addFilter(new EqualsFilter('payment_method.salesChannels.id', $context->getSalesChannelId()));
    }
}
