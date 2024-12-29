<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Validation;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidationFactoryInterface;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

#[Package('checkout')]
class AddressValidationFactory implements DataValidationFactoryInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly SystemConfigService $systemConfigService)
    {
    }

    public function create(SalesChannelContext $context): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('address.create');

        $this->buildCommonValidation($definition, $context);

        return $definition;
    }

    public function update(SalesChannelContext $context): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('address.update');

        $this->buildCommonValidation($definition, $context)
            ->add('id', new NotBlank(), new EntityExists(['context' => $context->getContext(), 'entity' => 'customer_address']));

        return $definition;
    }

    private function buildCommonValidation(DataValidationDefinition $definition, SalesChannelContext $context): DataValidationDefinition
    {
        $frameworkContext = $context->getContext();
        $salesChannelId = $context->getSalesChannel()->getId();

        $definition
            ->add('salutationId', new EntityExists(['entity' => 'salutation', 'context' => $frameworkContext]))
            ->add('countryId', new EntityExists(['entity' => 'country', 'context' => $frameworkContext]))
            ->add('countryStateId', new EntityExists(['entity' => 'country_state', 'context' => $frameworkContext]))
            ->add('name', new NotBlank(null, 'VIOLATION::NAME_IS_BLANK_ERROR'))
            ->add('street', new NotBlank(null, 'VIOLATION::STREET_IS_BLANK_ERROR'))
            ->add('city', new NotBlank(null, 'VIOLATION::CITY_IS_BLANK_ERROR'))
            ->add('countryId', new NotBlank(null, 'VIOLATION::COUNTRY_IS_BLANK_ERROR'), new EntityExists(['entity' => 'country', 'context' => $frameworkContext]));

        if ($this->systemConfigService->get('core.loginRegistration.showAdditionalAddressField1', $salesChannelId)
            && $this->systemConfigService->get('core.loginRegistration.additionalAddressField1Required', $salesChannelId)) {
            $definition->add('additionalAddressLine1', new NotBlank(null, 'VIOLATION::ADDITIONAL_ADDR1_IS_BLANK_ERROR'));
        }

        if ($this->systemConfigService->get('core.loginRegistration.showAdditionalAddressField2', $salesChannelId)
            && $this->systemConfigService->get('core.loginRegistration.additionalAddressField2Required', $salesChannelId)) {
            $definition->add('additionalAddressLine2', new NotBlank(null, 'VIOLATION::ADDITIONAL_ADDR2_IS_BLANK_ERROR'));
        }

        if ($this->systemConfigService->get('core.loginRegistration.showPhoneNumberField', $salesChannelId)
            && $this->systemConfigService->get('core.loginRegistration.phoneNumberFieldRequired', $salesChannelId)) {
            $definition->add('phoneNumber', new NotBlank(null, 'VIOLATION::PHONE_NUMBER_IS_BLANK_ERROR'));
        }

        if ($this->systemConfigService->get('core.loginRegistration.showPhoneNumberField', $salesChannelId)) {
            $definition->add('phoneNumber', new Length(['max' => CustomerAddressDefinition::MAX_LENGTH_PHONE_NUMBER], null, null, null, null, null, 'VIOLATION::PHONE_NUMBER_IS_TOO_LONG'));
        }

        $definition
            ->add('name', new Length(['max' => CustomerAddressDefinition::MAX_LENGTH_NAME], null, null, null, null, null, 'VIOLATION::NAME_IS_TOO_LONG'))
            ->add('title', new Length(['max' => CustomerAddressDefinition::MAX_LENGTH_TITLE], null, null, null, null, null, 'VIOLATION::TITLE_IS_TOO_LONG'))
            ->add('zipcode', new Length(['max' => CustomerAddressDefinition::MAX_LENGTH_ZIPCODE], null, null, null, null, null, 'VIOLATION::ZIPCODE_IS_TOO_LONG'));

        return $definition;
    }
}
