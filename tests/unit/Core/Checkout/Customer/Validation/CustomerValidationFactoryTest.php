<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\Validation;

use Cicada\Core\Checkout\Customer\Validation\CustomerProfileValidationFactory;
use Cicada\Core\Checkout\Customer\Validation\CustomerValidationFactory;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Faker\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CustomerValidationFactory::class)]
class CustomerValidationFactoryTest extends TestCase
{
    #[DataProvider('getCreateTestData')]
    public function testCreate(
        DataValidationDefinition $profileDefinition,
        DataValidationDefinition $expected
    ): void {
        $customerProfileValidationFactory = $this
            ->getMockBuilder(CustomerProfileValidationFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $customerProfileValidationFactory
            ->method('create')
            ->willReturn($profileDefinition);

        $customerValidationFactory = new CustomerValidationFactory($customerProfileValidationFactory);
        $context = $this
            ->getMockBuilder(SalesChannelContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $actual = $customerValidationFactory->create($context);

        static::assertEquals($expected, $actual);
    }

    public static function getCreateTestData(): \Generator
    {
        $faker = Factory::create();

        // test with no constraints added
        $profileDefinition = new DataValidationDefinition();
        $expected = new DataValidationDefinition('customer.create');
        self::addConstraints($expected);

        yield [$profileDefinition, $expected];

        // test merge
        $profileDefinition->add('email', new Type('string'));
        $expected->set('email', new Type('string'), new NotBlank(), new Email(null, 'VIOLATION::INVALID_EMAIL_FORMAT_ERROR'));

        yield [$profileDefinition, $expected];

        // test with randomized data
        for ($i = 0; $i < 10; ++$i) {
            $profileDefinition = new DataValidationDefinition();

            $notBlankName = $faker->name();
            $profileDefinition->add($notBlankName, new NotBlank(null, 'VIOLATION::NAME_IS_BLANK_ERROR'));

            $emailName = $faker->name();
            $profileDefinition->add($emailName, new Email(null, 'VIOLATION::INVALID_EMAIL_FORMAT_ERROR'));

            $expected = new DataValidationDefinition('customer.create');

            $expected->add($notBlankName, new NotBlank(null, 'VIOLATION::NAME_IS_BLANK_ERROR'));
            $expected->add($emailName, new Email(null, 'VIOLATION::INVALID_EMAIL_FORMAT_ERROR'));

            self::addConstraints($expected);

            yield [$profileDefinition, $expected];
        }
    }

    /**
     * @see CustomerValidationFactory::addConstraints
     */
    private static function addConstraints(DataValidationDefinition $definition): void
    {
        $definition->add('email', new NotBlank(), new Email(null, 'VIOLATION::INVALID_EMAIL_FORMAT_ERROR'));
        $definition->add('active', new Type('boolean'));
    }
}
