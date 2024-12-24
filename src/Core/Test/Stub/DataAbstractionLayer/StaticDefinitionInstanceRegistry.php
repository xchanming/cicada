<?php declare(strict_types=1);

namespace Cicada\Core\Test\Stub\DataAbstractionLayer;

use Cicada\Core\Framework\DataAbstractionLayer\Dbal\FieldAccessorBuilder\DefaultFieldAccessorBuilder;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\FieldAccessorBuilder\FieldAccessorBuilderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\BlobFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\BoolFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\CreatedAtFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\CustomFieldsSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\FieldSerializerInterface;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\FkFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\FloatFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\IdFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\IntFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\LongTextFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\ManyToManyAssociationFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\ManyToOneAssociationFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\OneToManyAssociationFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\OneToOneAssociationFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\StringFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\UpdatedAtFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteCommandExtractor;
use Cicada\Core\Framework\Util\HtmlSanitizer;
use Cicada\Core\System\CustomField\CustomFieldService;
use Cicada\Core\Test\Stub\Doctrine\FakeConnection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @final
 */
class StaticDefinitionInstanceRegistry extends DefinitionInstanceRegistry
{
    /**
     * @var FieldSerializerInterface[]
     */
    private array $serializers;

    /**
     * @param array<int|string, class-string<EntityDefinition>|EntityDefinition> $registeredDefinitions
     */
    public function __construct(
        array $registeredDefinitions,
        private readonly ValidatorInterface $validator,
        private readonly EntityWriteGatewayInterface $entityWriteGateway
    ) {
        parent::__construct(new ContainerBuilder(), [], []);

        $this->setUpSerializers();

        foreach ($registeredDefinitions as $serviceId => $definition) {
            $this->register(
                $definition instanceof EntityDefinition ? $definition : new $definition(),
                \is_string($serviceId) ? $serviceId : null
            );
        }
    }

    public function getSerializer(string $serializerClass): FieldSerializerInterface
    {
        return $this->serializers[$serializerClass];
    }

    public function getAccessorBuilder(string $accessorBuilderClass): FieldAccessorBuilderInterface
    {
        return new DefaultFieldAccessorBuilder();
    }

    private function setUpSerializers(): void
    {
        $this->serializers = [
            IdFieldSerializer::class => new IdFieldSerializer($this->validator, $this),
            FkFieldSerializer::class => new FkFieldSerializer($this->validator, $this),
            StringFieldSerializer::class => new StringFieldSerializer($this->validator, $this, new HtmlSanitizer()),
            LongTextFieldSerializer::class => new LongTextFieldSerializer($this->validator, $this, new HtmlSanitizer()),
            IntFieldSerializer::class => new IntFieldSerializer($this->validator, $this),
            FloatFieldSerializer::class => new FloatFieldSerializer($this->validator, $this),
            BoolFieldSerializer::class => new BoolFieldSerializer($this->validator, $this),
            JsonFieldSerializer::class => new JsonFieldSerializer($this->validator, $this),
            CreatedAtFieldSerializer::class => new CreatedAtFieldSerializer($this->validator, $this),
            UpdatedAtFieldSerializer::class => new UpdatedAtFieldSerializer($this->validator, $this),
            BlobFieldSerializer::class => new BlobFieldSerializer(),
            CustomFieldsSerializer::class => new CustomFieldsSerializer(
                $this,
                $this->validator,
                new CustomFieldService(new FakeConnection([['foo', 'int']]))
            ),
            ManyToManyAssociationFieldSerializer::class => new ManyToManyAssociationFieldSerializer(
                new WriteCommandExtractor($this->entityWriteGateway, $this),
            ),
            ManyToOneAssociationFieldSerializer::class => new ManyToOneAssociationFieldSerializer(
                new WriteCommandExtractor($this->entityWriteGateway, $this),
            ),
            OneToManyAssociationFieldSerializer::class => new OneToManyAssociationFieldSerializer(
                new WriteCommandExtractor($this->entityWriteGateway, $this),
            ),
            OneToOneAssociationFieldSerializer::class => new OneToOneAssociationFieldSerializer(
                new WriteCommandExtractor($this->entityWriteGateway, $this),
            ),
        ];
    }
}
