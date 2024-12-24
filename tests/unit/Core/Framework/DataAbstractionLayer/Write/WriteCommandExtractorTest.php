<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Write;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Context\ContextSource;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityWriteGateway;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\PrimaryKeyBag;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteCommandExtractor;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\WriteConstraintViolationException;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[CoversClass(WriteCommandExtractor::class)]
class WriteCommandExtractorTest extends TestCase
{
    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('writeProtectedFieldsProvider')]
    public function testExceptionForWriteProtectedFields(array $payload, ContextSource $scope, bool $valid): void
    {
        $definition = new class extends EntityDefinition {
            final public const ENTITY_NAME = 'webhook';

            public function getEntityName(): string
            {
                return self::ENTITY_NAME;
            }

            public function getDefaults(): array
            {
                return [
                    'errorCount' => 0,
                ];
            }

            protected function defineFields(): FieldCollection
            {
                return new FieldCollection([
                    (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
                    (new StringField('name', 'name'))->addFlags(new Required()),
                    (new IntField('error_count', 'errorCount', 0))->addFlags(new Required(), new WriteProtected(Context::SYSTEM_SCOPE)),
                ]);
            }
        };

        $data = [
            'name' => 'My super webhook',
        ];
        $data = \array_replace($data, $payload);

        $registry = new StaticDefinitionInstanceRegistry(
            [$definition],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );
        $extractor = new WriteCommandExtractor(
            $this->createMock(EntityWriteGateway::class),
            $registry
        );
        $context = Context::createDefaultContext($scope);

        $parameters = new WriteParameterBag(
            $registry->get($definition::class),
            WriteContext::createFromContext($context),
            '',
            new WriteCommandQueue(),
            new PrimaryKeyBag()
        );

        $extractor->extract($data, $parameters);

        if ($valid) {
            static::assertCount(0, $parameters->getContext()->getExceptions()->getExceptions());

            return;
        }

        static::assertCount(1, $parameters->getContext()->getExceptions()->getExceptions());
        $exception = $parameters->getContext()->getExceptions()->getExceptions();
        $exception = \array_shift($exception);

        static::assertInstanceOf(WriteConstraintViolationException::class, $exception);

        $violations = $exception->getViolations();
        static::assertCount(1, $violations);
        static::assertInstanceOf(ConstraintViolation::class, $violations->get(0));
        static::assertStringContainsString('This field is write-protected. (Got: "user" scope and "system" is required)', (string) $violations->get(0)->getMessage());
    }

    public static function writeProtectedFieldsProvider(): \Generator
    {
        yield 'Test write webhook with system source and valid error count' => [
            ['errorCount' => 10],
            new SystemSource(),
            true,
        ];

        yield 'Test write webhook with user source and valid error count' => [
            ['errorCount' => 10],
            new AdminApiSource(Uuid::randomHex()),
            false,
        ];

        yield 'Test write without error count and user source' => [
            [],
            new AdminApiSource(Uuid::randomHex()),
            true,
        ];
    }
}
