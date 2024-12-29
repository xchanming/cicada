<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\Validation\Constraint;

use Cicada\Core\Checkout\Customer\Validation\Constraint\CustomerVatIdentification;
use Cicada\Core\Checkout\Customer\Validation\Constraint\CustomerVatIdentificationValidator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Validation\HappyPathValidator;
use Cicada\Core\System\Country\CountryEntity;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[CoversClass(CustomerVatIdentificationValidator::class)]
class CustomerVatIdentificationValidatorTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const COUNTRY_ISO = [
        'CN',
    ];

    private CustomerVatIdentificationValidator $validator;

    private ExecutionContext $executionContext;

    /**
     * @var string[]
     */
    private readonly array $countries;

    protected function setUp(): void
    {
        $this->countries = $this->getCountries();

        $this->executionContext = new ExecutionContext(
            static::getContainer()->get(HappyPathValidator::class),
            null,
            static::getContainer()->get(TranslatorInterface::class),
        );

        $connection = static::getContainer()->get(Connection::class);

        $this->validator = new CustomerVatIdentificationValidator($connection);

        $this->validator->initialize($this->executionContext);
    }

    /**
     * @param array<int, string> $vatIds
     */
    #[DataProvider('dataProviderValidatesVatIdsCorrectly')]
    public function testValidatesVatIdsCorrectly(string $iso, array $vatIds): void
    {
        $constraint = new CustomerVatIdentification([
            'message' => 'Invalid VAT ID',
            'countryId' => $this->countries[$iso],
            'shouldCheck' => true,
        ]);

        $this->validator->validate($vatIds, $constraint);

        static::assertCount(3, $this->executionContext->getViolations());
    }

    /**
     * @param array<int, string> $vatIds
     */
    #[DataProvider('dataProviderValidatesVatIdsInCorrectly')]
    public function testValidateVatIdsInCorrectly(string $iso, int $count, array $vatIds): void
    {
        $constraint = new CustomerVatIdentification([
            'message' => 'Invalid VAT ID',
            'countryId' => $this->countries[$iso],
            'shouldCheck' => true,
        ]);

        $this->validator->validate($vatIds, $constraint);

        /** @var ConstraintViolationList $violations */
        $violations = $this->executionContext->getViolations();

        static::assertSame($violations->count(), $count);

        static::assertNotNull($violation = $violations->get(0));
        static::assertInstanceOf(ConstraintViolation::class, $violation);

        static::assertEquals('Invalid VAT ID', $violation->getMessage());
        static::assertEquals($violation->getParameters(), ['{{ vatId }}' => '"' . $vatIds[0] . '"']);
        static::assertEquals(CustomerVatIdentification::VAT_ID_FORMAT_NOT_CORRECT, $violation->getCode());
    }

    public function testDoesNotValidateWhenVatIdsIsNull(): void
    {
        $constraint = new CustomerVatIdentification([
            'message' => 'Invalid VAT ID',
            'countryId' => $this->countries['CN'],
            'shouldCheck' => true,
        ]);

        $this->validator->validate(null, $constraint);

        static::assertCount(0, $this->executionContext->getViolations());
    }

    public function testDoesNotValidateWhenShouldCheckIsFalse(): void
    {
        $constraint = new CustomerVatIdentification([
            'message' => 'Invalid VAT ID',
            'countryId' => $this->countries['CN'],
            'shouldCheck' => false,
        ]);

        $this->validator->validate(['DE123456789'], $constraint);

        static::assertCount(0, $this->executionContext->getViolations());
    }

    /**
     * @return iterable<string, array<int, array<int, string>|string>>
     */
    public static function dataProviderValidatesVatIdsCorrectly(): iterable
    {
        yield 'valid vat with Austria' => ['CN', ['ATU12345678', 'ATU87654321', 'ATU23456789']];
    }

    /**
     * @return iterable<string, array<int, array<int, string>|int|string>>
     */
    public static function dataProviderValidatesVatIdsInCorrectly(): iterable
    {
        yield 'invalid vat with Germany' => [
            'CN',
            6,
            ['AADE1234567', '123456789', 'DE12345678', 'DEC123456789', '123456789DE', 'DE123456789'],
        ];
    }

    /**
     * @return array<string>
     */
    private function getCountries(): array
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->setLimit(\count(self::COUNTRY_ISO));

        $criteria->addFilter(new EqualsAnyFilter('iso', self::COUNTRY_ISO));

        $repo = static::getContainer()->get('country.repository');

        $countries = $repo->search($criteria, $context)->fmap(function (CountryEntity $country) {
            return $country->getIso();
        });

        return array_flip($countries);
    }
}
