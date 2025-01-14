<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Validation;

use Cicada\Core\Checkout\Customer\Validation\Constraint\CustomerPhoneNumberUnique;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\Framework\Validation\Exception\ConstraintViolationException;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class CustomerPhoneNumberUniqueValidatorTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    public function testSameCustomerPhoneNumberWithExistedBoundAccount(): void
    {
        $phoneNumber = '18000000000';

        $salesChannelContext1 = $this->createSalesChannelContext();
        $this->createCustomerOfSalesChannel($salesChannelContext1->getSalesChannelId(), $phoneNumber);

        $salesChannelParameters = [
            'domains' => [
                [
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'currencyId' => Defaults::CURRENCY,
                    'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                    'url' => 'http://localhost2',
                ],
            ],
        ];

        $salesChannelContext2 = $this->createSalesChannelContext($salesChannelParameters);

        $constraint = new CustomerPhoneNumberUnique([
            'context' => $salesChannelContext2->getContext(),
            'salesChannelContext' => $salesChannelContext2,
        ]);

        $validation = new DataValidationDefinition('customer.phoneNumber.update');
        $validation->add('phoneNumber', $constraint);

        $validator = static::getContainer()->get(DataValidator::class);
        $validator->validate(['phoneNumber' => $phoneNumber], $validation);
    }

    public function testSameCustomerPhoneNumberOnSameSalesChannel(): void
    {
        $phoneNumber = '18000000000';

        $salesChannelContext1 = $this->createSalesChannelContext();
        $this->createCustomerOfSalesChannel($salesChannelContext1->getSalesChannelId(), $phoneNumber);

        $constraint = new CustomerPhoneNumberUnique([
            'context' => $salesChannelContext1->getContext(),
            'salesChannelContext' => $salesChannelContext1,
        ]);

        $validation = new DataValidationDefinition('customer.phoneNumber.update');

        $validation->add('phoneNumber', $constraint);

        $validator = static::getContainer()->get(DataValidator::class);

        try {
            $validator->validate([
                'phoneNumber' => $phoneNumber,
            ], $validation);

            static::fail('No exception is thrown');
        } catch (\Throwable $exception) {
            static::assertInstanceOf(ConstraintViolationException::class, $exception);
            $violations = $exception->getViolations();
            $violation = $violations->get(1);

            static::assertNotEmpty($violation);
            static::assertEquals($constraint->message, $violation->getMessageTemplate());
        }
    }

    public function testSameCustomerPhoneNumberWithExistedNonBoundAccount(): void
    {
        $phoneNumber = '18000000000';

        $salesChannelContext1 = $this->createSalesChannelContext();
        $this->createCustomerOfSalesChannel($salesChannelContext1->getSalesChannelId(), $phoneNumber);

        $constraint = new CustomerPhoneNumberUnique([
            'context' => $salesChannelContext1->getContext(),
            'salesChannelContext' => $salesChannelContext1,
        ]);

        $validation = new DataValidationDefinition('customer.phoneNumber.update');

        $validation->add('phoneNumber', $constraint);

        $validator = static::getContainer()->get(DataValidator::class);

        try {
            $validator->validate([
                'phoneNumber' => $phoneNumber,
            ], $validation);

            static::fail('No exception is thrown');
        } catch (\Throwable $exception) {
            static::assertInstanceOf(ConstraintViolationException::class, $exception);
            $violations = $exception->getViolations();
            $violation = $violations->get(1);

            static::assertNotEmpty($violation);
            static::assertEquals($constraint->message, $violation->getMessageTemplate());
        }
    }

    private function createCustomerOfSalesChannel(string $salesChannelId, string $phoneNmber, bool $boundToSalesChannel = true): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'number' => '1337',
            'salutationId' => $this->getValidSalutationId(),
            'name' => 'Max',
            'customerNumber' => '1337',
            'email' => 'john.doe@example.com',
            'phoneNumber' => $phoneNmber,
            'password' => TestDefaults::HASHED_PASSWORD,
            'boundSalesChannelId' => $boundToSalesChannel ? $salesChannelId : null,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => $salesChannelId,
        ];

        static::getContainer()
            ->get('customer.repository')
            ->upsert([$customer], Context::createDefaultContext());

        return $customerId;
    }
}
