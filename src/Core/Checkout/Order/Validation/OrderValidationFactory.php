<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Validation;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidationFactoryInterface;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\NotBlank;

#[Package('checkout')]
class OrderValidationFactory implements DataValidationFactoryInterface
{
    public function create(SalesChannelContext $context): DataValidationDefinition
    {
        return $this->createOrderValidation('order.create');
    }

    public function update(SalesChannelContext $context): DataValidationDefinition
    {
        return $this->createOrderValidation('order.update');
    }

    private function createOrderValidation(string $validationName): DataValidationDefinition
    {
        $definition = new DataValidationDefinition($validationName);

        $definition->add('tos', new NotBlank());

        return $definition;
    }
}
