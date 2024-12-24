<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Validation;

use Cicada\Core\Checkout\Customer\Validation\Constraint\CustomerVatIdentification;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\Framework\Validation\Exception\ConstraintViolationException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class CustomerVatIdentificationValidatorTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    public function testValidateVatIds(): void
    {
        $vatIds = [
            '123546',
        ];

        $constraint = new CustomerVatIdentification([
            'countryId' => $this->getValidCountryId(),
        ]);

        $validation = new DataValidationDefinition('customer.create');

        $validation
            ->add('vatIds', $constraint);

        $validator = static::getContainer()->get(DataValidator::class);

        try {
            $validator->validate(['vatIds' => $vatIds], $validation);
        } catch (\Throwable $exception) {
            static::assertInstanceOf(ConstraintViolationException::class, $exception);
            $violations = $exception->getViolations();
            $violation = $violations->get(1);

            static::assertNotEmpty($violation);
            static::assertEquals($constraint->message, $violation->getMessageTemplate());
        }
    }
}
