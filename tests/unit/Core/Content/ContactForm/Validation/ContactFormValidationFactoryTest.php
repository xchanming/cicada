<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\ContactForm\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\ContactForm\Validation\ContactFormValidationFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(ContactFormValidationFactory::class)]
class ContactFormValidationFactoryTest extends TestCase
{
    #[DataProvider('systemConfigDataProvider')]
    public function testCreate(bool $required, \Closure $expectsClosure): void
    {
        $systemConfigServiceMock = $this->createMock(SystemConfigService::class);
        $systemConfigServiceMock->method('get')->willReturn($required);

        $validation = new ContactFormValidationFactory(
            $this->createMock(EventDispatcherInterface::class),
            $systemConfigServiceMock
        );

        $contextMock = $this->createMock(SalesChannelContext::class);

        $definition = $validation->create($contextMock);

        $expectsClosure($definition, $contextMock);
    }

    public static function systemConfigDataProvider(): \Generator
    {
        yield 'is required' => [
            true,
            function (DataValidationDefinition $definition, SalesChannelContext $context): void {
                static::assertEquals($definition->getProperties(), [
                    'salutationId' => [
                        new NotBlank(),
                        new EntityExists(['entity' => 'salutation', 'context' => $context->getContext()]),
                    ],
                    'email' => [new NotBlank(), new Email()],
                    'subject' => [new NotBlank()],
                    'comment' => [new NotBlank()],
                    'firstName' => [
                        new NotBlank(),
                        new Regex(['pattern' => ContactFormValidationFactory::DOMAIN_NAME_REGEX, 'match' => false]),
                    ],
                    'lastName' => [
                        new NotBlank(),
                        new Regex(['pattern' => ContactFormValidationFactory::DOMAIN_NAME_REGEX, 'match' => false]),
                    ],
                    'phone' => [new NotBlank()],
                ]);
            },
        ];

        yield 'is not required' => [
            false,
            function (DataValidationDefinition $definition, SalesChannelContext $context): void {
                static::assertEquals($definition->getProperties(), [
                    'salutationId' => [
                        new NotBlank(),
                        new EntityExists(['entity' => 'salutation', 'context' => $context->getContext()]),
                    ],
                    'email' => [new NotBlank(), new Email()],
                    'subject' => [new NotBlank()],
                    'comment' => [new NotBlank()],
                    'firstName' => [
                        new Regex(['pattern' => ContactFormValidationFactory::DOMAIN_NAME_REGEX, 'match' => false]),
                    ],
                    'lastName' => [
                        new Regex(['pattern' => ContactFormValidationFactory::DOMAIN_NAME_REGEX, 'match' => false]),
                    ],
                ]);
            },
        ];
    }
}
