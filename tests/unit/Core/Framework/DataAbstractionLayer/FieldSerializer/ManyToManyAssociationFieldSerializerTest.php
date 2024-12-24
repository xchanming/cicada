<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\FieldSerializer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\ManyToManyAssociationFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\FieldException\ExpectedArrayException;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteCommandExtractor;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticDefinitionInstanceRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[CoversClass(ManyToManyAssociationFieldSerializer::class)]
class ManyToManyAssociationFieldSerializerTest extends TestCase
{
    public function testExceptionIsThrownIfSubresourceNotArray(): void
    {
        new StaticDefinitionInstanceRegistry(
            [
                'Media' => $mediaDefinition = new MediaDefinition(),
                'MediaGallery' => new MediaGalleryDefinition(),
                'MediaGalleryMapping' => new MediaGalleryMappingDefinition(),
            ],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class)
        );

        $field = $mediaDefinition->getField('galleries');

        static::assertInstanceOf(ManyToManyAssociationField::class, $field);

        $serializer = new ManyToManyAssociationFieldSerializer($this->createMock(WriteCommandExtractor::class));

        $params = new WriteParameterBag(
            $mediaDefinition,
            WriteContext::createFromContext(Context::createDefaultContext()),
            '',
            new WriteCommandQueue()
        );

        $this->expectException(ExpectedArrayException::class);
        $this->expectExceptionMessage('Expected data at /galleries/0 to be an array.');

        $serializer->normalize($field, [
            'galleries' => [
                'should-be-an-array',
            ],
        ], $params);
    }

    public function testDecodeThrowsException(): void
    {
        $serializer = new ManyToManyAssociationFieldSerializer($this->createMock(WriteCommandExtractor::class));

        $this->expectException(DataAbstractionLayerException::class);
        $this->expectExceptionMessage(\sprintf('Decoding of %s is handled by the entity hydrator.', ManyToManyAssociationField::class));

        $serializer->decode(
            new ManyToManyAssociationField(
                'galleries',
                'MediaGallery',
                'MediaGalleryMapping',
                'media_id',
                'gallery_id',
            ),
            []
        );
    }

    public function testNormalizeThrowsExceptionIfMappingDefinitionHasNoForeignKeys(): void
    {
        $mediaDefinition = new MediaDefinition();
        $mediaGalleryDefinition = new MediaGalleryDefinition();
        $mediaGalleryMappingDefinition = new MediaGalleryMappingDefinition();

        new StaticDefinitionInstanceRegistry(
            [
                'Media' => $mediaDefinition,
                'MediaGallery' => $mediaGalleryDefinition,
                'MediaGalleryMapping' => $mediaGalleryMappingDefinition,
            ],
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityWriteGatewayInterface::class),
        );

        $field = $mediaDefinition->getField('galleries');

        static::assertInstanceOf(ManyToManyAssociationField::class, $field);

        $serializer = new ManyToManyAssociationFieldSerializer($this->createMock(WriteCommandExtractor::class));

        $params = new WriteParameterBag(
            $mediaDefinition,
            WriteContext::createFromContext(Context::createDefaultContext()),
            '',
            new WriteCommandQueue()
        );

        $this->expectException(DataAbstractionLayerException::class);
        $this->expectExceptionMessage(\sprintf('Foreign key for association "galleries" not found. Please add one to "%s"', MediaGalleryMappingDefinition::class));
        $serializer->normalize($field, [
            'galleries' => [
                ['id' => 'gallery-id-1'],
                ['id' => 'gallery-id-2'],
            ],
        ], $params);
    }
}

/**
 * @internal
 */
class MediaDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'media';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new StringField('file_extension', 'fileExtension'))->addFlags(new ApiAware()),
            new ManyToManyAssociationField(
                'galleries',
                'MediaGallery',
                'MediaGalleryMapping',
                'media_id',
                'gallery_id',
            ),
        ]);
    }
}

/**
 * @internal
 */
class MediaGalleryDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'media_gallery';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('title', 'title'))->addFlags(new Required()),

            new ManyToManyAssociationField(
                'media',
                'Media',
                'MediaGalleryMapping',
                'gallery_id',
                'media_id'
            ),
        ]);
    }
}

/**
 * @internal
 */
class MediaGalleryMappingDefinition extends MappingEntityDefinition
{
    public function getEntityName(): string
    {
        return 'media_gallery_mapping';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            // No defined FK fields on purpose
            new ManyToOneAssociationField('media', 'media_id', 'Media', 'id'),
            new ManyToOneAssociationField('galleries', 'gallery_id', 'MediaGallery', 'id'),
        ]);
    }
}
