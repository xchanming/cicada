<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Validation;

use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Customer\Validation\Constraint\CustomerPhoneNumberUnique;
use Cicada\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidationFactoryInterface;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\Salutation\SalutationDefinition;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

#[Package('checkout')]
class CustomerProfileValidationFactory implements DataValidationFactoryInterface
{
    /**
     * @param string[] $accountTypes
     *
     * @internal
     */
    public function __construct(
        private readonly SalutationDefinition $salutationDefinition,
        private readonly SystemConfigService $systemConfigService,
        private readonly array $accountTypes
    ) {
    }

    public function create(SalesChannelContext $context): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('customer.profile.create');

        $this->addConstraints($definition, $context);

        return $definition;
    }

    public function update(SalesChannelContext $context): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('customer.profile.update');

        $this->addConstraints($definition, $context);

        return $definition;
    }

    private function addConstraints(DataValidationDefinition $definition, SalesChannelContext $context): void
    {
        $frameworkContext = $context->getContext();
        $salesChannelId = $context->getSalesChannelId();

        $definition
            ->add('salutationId', new EntityExists(['entity' => $this->salutationDefinition->getEntityName(), 'context' => $frameworkContext]))
            ->add('title', new Length(['max' => CustomerDefinition::MAX_LENGTH_TITLE]))
            ->add('name', new NotBlank(), new Length(['max' => CustomerDefinition::MAX_LENGTH_NAME]))
            ->add('accountType', new Choice($this->accountTypes));

        if ($this->systemConfigService->get('core.loginRegistration.birthdayFieldRequired', $salesChannelId)) {
            $definition
                ->add('birthdayDay', new GreaterThanOrEqual(['value' => 1]), new LessThanOrEqual(['value' => 31]))
                ->add('birthdayMonth', new GreaterThanOrEqual(['value' => 1]), new LessThanOrEqual(['value' => 12]))
                ->add('birthdayYear', new GreaterThanOrEqual(['value' => 1900]), new LessThanOrEqual(['value' => date('Y')]));
        }
        if ($this->systemConfigService->get('core.loginRegistration.phoneNumberFieldRequired', $salesChannelId)) {
            $definition->add('phoneNumber', new NotBlank(null, 'VIOLATION::PHONE_NUMBER_IS_BLANK_ERROR'));
            $options = ['context' => $context->getContext(), 'salesChannelContext' => $context];
            $definition->add('phoneNumber', new CustomerPhoneNumberUnique($options));
            $definition->add('phoneNumber', new Length(['max' => CustomerDefinition::MAX_LENGTH_PHONE_NUMBER], null, null, null, null, null, 'VIOLATION::PHONE_NUMBER_IS_TOO_LONG'));
        }
        if ($this->systemConfigService->get('core.loginRegistration.titleFieldRequired', $salesChannelId)) {
            $definition->add('title', new NotBlank(null, 'VIOLATION::TITLE_IS_BLANK_ERROR'));
            $definition->add('title', new Length(['max' => CustomerDefinition::MAX_LENGTH_TITLE], null, null, null, null, null, 'VIOLATION::TITLE_NUMBER_IS_TOO_LONG'));
        }
    }
}
