<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\ContactForm\SalesChannel;

use Cicada\Core\Content\ContactForm\SalesChannel\ContactFormRoute;
use Cicada\Core\Content\ContactForm\Validation\ContactFormValidationFactory;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\RateLimiter\RateLimiter;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidationFactoryInterface;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(ContactFormRoute::class)]
class ContactFormRouteTest extends TestCase
{
    private SalesChannelContext $salesChannelContext;

    protected function setUp(): void
    {
        $this->salesChannelContext = $this->createMock(SalesChannelContext::class);
    }

    /**
     * @param array<string, string> $data
     * @param array<string, string> $properties
     * @param array<int, mixed> $constraints
     */
    #[DataProvider('validatorDataProvider')]
    public function testSubscribeWithValidation(array $data, array $properties, array $constraints): void
    {
        $requestData = new RequestDataBag();
        $requestData->add($data);

        $newsletterRecipientEntity = new NewsletterRecipientEntity();
        $newsletterRecipientEntity->setId(Uuid::randomHex());
        $newsletterRecipientEntity->setConfirmedAt(new \DateTime());

        $salutationEntitySearchResult = new EntitySearchResult(
            'salutation',
            1,
            new EntityCollection([]),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->expects(static::once())->method('search')->willReturn($salutationEntitySearchResult);

        $mock = $this->createMock(DataValidator::class);
        $mock->method('validate')->willReturnCallback(function (array $data, DataValidationDefinition $definition) use ($properties, $constraints): void {
            foreach ($properties as $propertyName => $value) {
                static::assertEquals($value, $data[$propertyName] ?? null);
                static::assertEquals($definition->getProperties()[$propertyName] ?? null, $constraints);
            }
        });

        $contactFormRoute = new ContactFormRoute(
            $this->createMock(DataValidationFactoryInterface::class),
            $mock,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(SystemConfigService::class),
            $entityRepository,
            $entityRepository,
            $entityRepository,
            $entityRepository,
            $entityRepository,
            $this->createMock(RequestStack::class),
            $this->createMock(RateLimiter::class)
        );

        $contactFormRoute->load($requestData, $this->salesChannelContext);
    }

    public static function validatorDataProvider(): \Generator
    {
        yield 'subscribe with no correct validation' => [
            [
                'email' => 'test@example.com',
                'option' => 'direct',
                'firstName' => 'Y http://localhost',
                'lastName' => 'Tran http://localhost',
                'salutationId' => Uuid::randomHex(),
            ],
            ['firstName' => 'Y http://localhost', 'lastName' => 'Tran http://localhost'],
            [
                new NotBlank(),
                new Regex([
                    'pattern' => ContactFormValidationFactory::DOMAIN_NAME_REGEX,
                    'match' => false,
                ]),
            ],
        ];

        yield 'subscribe correct is validation' => [
            [
                'email' => 'test@example.com',
                'option' => 'direct',
                'firstName' => 'Y',
                'lastName' => 'Tran',
                'salutationId' => Uuid::randomHex(),
            ],
            ['firstName' => 'Y', 'lastName' => 'Tran'],
            [
                new NotBlank(),
                new Regex([
                    'pattern' => ContactFormValidationFactory::DOMAIN_NAME_REGEX,
                    'match' => false,
                ]),
            ],
        ];
    }
}
