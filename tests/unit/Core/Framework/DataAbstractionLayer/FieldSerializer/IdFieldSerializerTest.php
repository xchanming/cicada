<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\FieldSerializer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\IdFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Cicada\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(IdFieldSerializer::class)]
class IdFieldSerializerTest extends TestCase
{
    #[DataProvider('valueProvider')]
    public function testSerializer(Field $field, ?string $value, ?string $expected = null): void
    {
        $validator = $this->createMock(ValidatorInterface::class);

        $serializer = new IdFieldSerializer(
            $validator,
            new StaticDefinitionInstanceRegistry([], $validator, $this->createMock(EntityWriteGatewayInterface::class))
        );

        $existence = EntityExistence::createEmpty();
        $kv = new KeyValuePair($field->getPropertyName(), $value, true);
        $params = new WriteParameterBag(
            new ProductDefinition(),
            WriteContext::createFromContext(Context::createDefaultContext()),
            '',
            new WriteCommandQueue()
        );

        $encoded = iterator_to_array($serializer->encode($field, $existence, $kv, $params));

        static::assertArrayHasKey('media_id', $encoded);

        if ($expected !== null) {
            static::assertSame($expected, $encoded['media_id']);
        } else {
            static::assertTrue(Uuid::isValid(Uuid::fromBytesToHex($encoded['media_id'])));
        }
    }

    public static function valueProvider(): \Generator
    {
        $ids = new IdsCollection();

        yield 'field is pk and not explicitly passed (should be generated)' => [
            'field' => (new IdField('media_id', 'mediaId'))->addFlags(new PrimaryKey()),
            'value' => null,
        ];

        yield 'field is required and not explicitly passed (should be generated)' => [
            'field' => (new IdField('media_id', 'mediaId'))->addFlags(new Required()),
            'value' => null,
        ];

        yield 'field is pk and required and not explicitly passed (should be generated)' => [
            'field' => (new IdField('media_id', 'mediaId'))->addFlags(new PrimaryKey(), new Required()),
            'value' => null,
        ];

        yield 'field is pk and specified (should not be generated)' => [
            'field' => (new IdField('media_id', 'mediaId'))->addFlags(new PrimaryKey()),
            'value' => $ids->get('media-1'),
            'expected' => $ids->getBytes('media-1'),
        ];

        yield 'field is required and specified (should not be generated)' => [
            'field' => (new IdField('media_id', 'mediaId'))->addFlags(new Required()),
            'value' => $ids->get('media-1'),
            'expected' => $ids->getBytes('media-1'),
        ];

        yield 'field is pk and required and specified (should not be generated)' => [
            'field' => (new IdField('media_id', 'mediaId'))->addFlags(new PrimaryKey(), new Required()),
            'value' => $ids->get('media-1'),
            'expected' => $ids->getBytes('media-1'),
        ];
    }
}
