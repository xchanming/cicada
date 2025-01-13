<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Validation\CustomerProfileValidationFactory;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\System\Salutation\SalutationDefinition;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use Cicada\Core\Test\TestDefaults;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CustomerProfileValidationFactory::class)]
class CustomerProfileValidationFactoryTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $accountTypes;

    private SalutationDefinition $salutationDefinition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountTypes = [CustomerEntity::ACCOUNT_TYPE_BUSINESS, CustomerEntity::ACCOUNT_TYPE_PRIVATE];
        $this->salutationDefinition = new SalutationDefinition();
    }

    public function testCreateWithSalesChannelContext(): void
    {
        $customerProfileValidationFactory = new CustomerProfileValidationFactory(
            $this->salutationDefinition,
            $this->createMock(SystemConfigService::class),
            $this->accountTypes,
        );

        $salesChannelContext = $this->getSalesChannelContext();
        $actual = $customerProfileValidationFactory->create($salesChannelContext);
        $expected = new DataValidationDefinition('customer.profile.create');
        $this->addConstraintsSalesChannelContext($expected, $salesChannelContext);

        static::assertEquals($expected, $actual);
    }

    public function testCreateWithSalesChannelContextButBirthdayFieldIsHidden(): void
    {
        $configService = new StaticSystemConfigService([
            TestDefaults::SALES_CHANNEL => ['core.loginRegistration.showBirthdayField' => false],
        ]);

        $customerProfileValidationFactory = new CustomerProfileValidationFactory(
            $this->salutationDefinition,
            $configService,
            $this->accountTypes,
        );

        $salesChannelContext = $this->getSalesChannelContext();
        $actual = $customerProfileValidationFactory->create($salesChannelContext);
        $expected = new DataValidationDefinition('customer.profile.create');
        $this->addConstraintsSalesChannelContext($expected, $salesChannelContext);

        static::assertEquals($expected, $actual);
    }

    public function testCreateWithSalesChannelContextButBirthdayFieldIsOptional(): void
    {
        $configService = new StaticSystemConfigService([
            TestDefaults::SALES_CHANNEL => [
                'core.loginRegistration.showBirthdayField' => true,
                'core.loginRegistration.birthdayFieldRequired' => false,
            ],
        ]);

        $customerProfileValidationFactory = new CustomerProfileValidationFactory(
            $this->salutationDefinition,
            $configService,
            $this->accountTypes,
        );

        $salesChannelContext = $this->getSalesChannelContext();
        $actual = $customerProfileValidationFactory->create($salesChannelContext);
        $expected = new DataValidationDefinition('customer.profile.create');
        $this->addConstraintsSalesChannelContext($expected, $salesChannelContext);

        static::assertEquals($expected, $actual);
    }

    public function testCreateWithSalesChannelContextButBirthdayFieldIsRequired(): void
    {
        $configService = new StaticSystemConfigService([
            TestDefaults::SALES_CHANNEL => [
                'core.loginRegistration.showBirthdayField' => true,
                'core.loginRegistration.birthdayFieldRequired' => true,
            ],
        ]);

        $customerProfileValidationFactory = new CustomerProfileValidationFactory(
            $this->salutationDefinition,
            $configService,
            $this->accountTypes,
        );

        $salesChannelContext = $this->getSalesChannelContext();
        $actual = $customerProfileValidationFactory->create($salesChannelContext);
        $expected = new DataValidationDefinition('customer.profile.create');
        $this->addConstraintsSalesChannelContext($expected, $salesChannelContext);
        $this->addConstraintsBirthday($expected);

        static::assertEquals($expected, $actual);
    }

    public function testUpdateWithSalesChannelContext(): void
    {
        $customerProfileValidationFactory = new CustomerProfileValidationFactory(
            $this->salutationDefinition,
            $this->createMock(SystemConfigService::class),
            $this->accountTypes,
        );

        $salesChannelContext = $this->getSalesChannelContext();
        $actual = $customerProfileValidationFactory->update($salesChannelContext);
        $expected = new DataValidationDefinition('customer.profile.update');
        $this->addConstraintsSalesChannelContext($expected, $salesChannelContext);

        static::assertEquals($expected, $actual);
    }

    public function testUpdateWithSalesChannelContextButBirthdayFieldIsHidden(): void
    {
        $configService = new StaticSystemConfigService([
            TestDefaults::SALES_CHANNEL => ['core.loginRegistration.showBirthdayField' => false],
        ]);

        $customerProfileValidationFactory = new CustomerProfileValidationFactory(
            $this->salutationDefinition,
            $configService,
            $this->accountTypes,
        );

        $salesChannelContext = $this->getSalesChannelContext();
        $actual = $customerProfileValidationFactory->update($salesChannelContext);
        $expected = new DataValidationDefinition('customer.profile.update');
        $this->addConstraintsSalesChannelContext($expected, $salesChannelContext);

        static::assertEquals($expected, $actual);
    }

    public function testUpdateWithSalesChannelContextButBirthdayFieldIsOptional(): void
    {
        $configService = new StaticSystemConfigService([
            TestDefaults::SALES_CHANNEL => [
                'core.loginRegistration.showBirthdayField' => true,
                'core.loginRegistration.birthdayFieldRequired' => false,
            ],
        ]);

        $customerProfileValidationFactory = new CustomerProfileValidationFactory(
            $this->salutationDefinition,
            $configService,
            $this->accountTypes,
        );

        $salesChannelContext = $this->getSalesChannelContext();
        $actual = $customerProfileValidationFactory->update($salesChannelContext);
        $expected = new DataValidationDefinition('customer.profile.update');
        $this->addConstraintsSalesChannelContext($expected, $salesChannelContext);

        static::assertEquals($expected, $actual);
    }

    public function testUpdateWithSalesChannelContextButBirthdayFieldIsRequired(): void
    {
        $configService = new StaticSystemConfigService([
            TestDefaults::SALES_CHANNEL => [
                'core.loginRegistration.showBirthdayField' => true,
                'core.loginRegistration.birthdayFieldRequired' => true,
            ],
        ]);

        $customerProfileValidationFactory = new CustomerProfileValidationFactory(
            $this->salutationDefinition,
            $configService,
            $this->accountTypes,
        );

        $salesChannelContext = $this->getSalesChannelContext();
        $actual = $customerProfileValidationFactory->update($salesChannelContext);
        $expected = new DataValidationDefinition('customer.profile.update');
        $this->addConstraintsSalesChannelContext($expected, $salesChannelContext);
        $this->addConstraintsBirthday($expected);

        static::assertEquals($expected, $actual);
    }

    private function getSalesChannelContext(): SalesChannelContext
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(TestDefaults::SALES_CHANNEL);
        $salesChannel->setLanguageId(Defaults::LANGUAGE_SYSTEM);

        return Generator::generateSalesChannelContext(
            salesChannel: $salesChannel,
        );
    }

    private function addConstraintsSalesChannelContext(DataValidationDefinition $definition, SalesChannelContext $context): void
    {
        $definition
            ->add('salutationId', new EntityExists(['entity' => $this->salutationDefinition->getEntityName(), 'context' => $context->getContext()]))
            ->add('name', new NotBlank())
            ->add('accountType', new Choice($this->accountTypes))
            ->add('title', new Length(['max' => CustomerDefinition::MAX_LENGTH_TITLE]));

        $definition
            ->add('name', new Length(['max' => CustomerDefinition::MAX_LENGTH_NAME]));
    }

    private function addConstraintsBirthday(DataValidationDefinition $definition): void
    {
        $definition
            ->add('birthdayDay', new GreaterThanOrEqual(['value' => 1]), new LessThanOrEqual(['value' => 31]))
            ->add('birthdayMonth', new GreaterThanOrEqual(['value' => 1]), new LessThanOrEqual(['value' => 12]))
            ->add('birthdayYear', new GreaterThanOrEqual(['value' => 1900]), new LessThanOrEqual(['value' => date('Y')]));
    }
}
