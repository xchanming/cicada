<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity;

use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity\SalutationSerializer;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Salutation\SalutationCollection;
use Cicada\Core\System\Salutation\SalutationDefinition;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
class SalutationSerializerTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var EntityRepository<SalutationCollection>
     */
    private EntityRepository $salutationRepository;

    private SalutationSerializer $serializer;

    protected function setUp(): void
    {
        $this->salutationRepository = static::getContainer()->get('salutation.repository');
        $serializerRegistry = static::getContainer()->get(SerializerRegistry::class);

        $this->serializer = new SalutationSerializer($this->salutationRepository);
        $this->serializer->setRegistry($serializerRegistry);
    }

    public function testSimple(): void
    {
        $config = new Config([], [], []);

        $salutation = [
            'id' => Uuid::randomHex(),
            'salutationKey' => 'mrs',
            'translations' => [
                Defaults::LANGUAGE_SYSTEM => [
                    'displayName' => 'Mrs.',
                    'letterName' => 'Dear Mrs.',
                ],
            ],
        ];

        $serialized = iterator_to_array($this->serializer->serialize($config, $this->salutationRepository->getDefinition(), $salutation));

        $deserialized = iterator_to_array($this->serializer->deserialize($config, $this->salutationRepository->getDefinition(), $serialized));

        $expectedTranslations = $salutation['translations'][Defaults::LANGUAGE_SYSTEM];
        $actualTranslations = $deserialized['translations'][Defaults::LANGUAGE_SYSTEM];
        unset($salutation['translations'], $deserialized['translations']);

        static::assertEquals($salutation, $deserialized);
        static::assertEquals($expectedTranslations, $actualTranslations);
    }

    public function testDeserializeOnlySalutationKey(): void
    {
        $config = new Config([], [], []);

        $salutation = [
            'salutationKey' => 'mrs',
        ];

        $deserialized = iterator_to_array($this->serializer->deserialize($config, $this->salutationRepository->getDefinition(), $salutation));

        static::assertSame($salutation['salutationKey'], $deserialized['salutationKey']);
        static::assertArrayHasKey('id', $deserialized);

        $criteria = (new Criteria())->addFilter(new EqualsFilter('salutationKey', 'mrs'));
        $salutationId = $this->salutationRepository->searchIds($criteria, Context::createDefaultContext())->firstId();

        static::assertSame($salutationId, $deserialized['id']);
    }

    public function testUsesNotSpecifiedAsFallback(): void
    {
        $config = new Config([], [], []);

        $salutation = [
            'salutationKey' => 'unknown',
        ];

        $deserialized = iterator_to_array($this->serializer->deserialize($config, $this->salutationRepository->getDefinition(), $salutation));

        static::assertArrayNotHasKey('salutationKey', $deserialized);
        static::assertArrayHasKey('id', $deserialized);

        $criteria = (new Criteria())->addFilter(new EqualsFilter('salutationKey', 'not_specified'));
        $salutationId = $this->salutationRepository->searchIds($criteria, Context::createDefaultContext())->firstId();

        static::assertSame($salutationId, $deserialized['id']);
    }

    public function testSupportsOnlySalutation(): void
    {
        $serializer = new SalutationSerializer(static::getContainer()->get('salutation.repository'));

        $definitionRegistry = static::getContainer()->get(DefinitionInstanceRegistry::class);
        foreach ($definitionRegistry->getDefinitions() as $definition) {
            $entity = $definition->getEntityName();

            if ($entity === SalutationDefinition::ENTITY_NAME) {
                static::assertTrue($serializer->supports($entity));
            } else {
                static::assertFalse(
                    $serializer->supports($entity),
                    SalutationDefinition::class . ' should not support ' . $entity
                );
            }
        }
    }
}
