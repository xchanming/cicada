<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity;

use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity\CountrySerializer;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\Country\CountryDefinition;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class CountrySerializerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $countryRepository;

    private CountrySerializer $serializer;

    private string $countryId = '67d89afb684e44eeacd71ba1f59a5ae1';

    protected function setUp(): void
    {
        $this->countryRepository = static::getContainer()->get('country.repository');
        $serializerRegistry = static::getContainer()->get(SerializerRegistry::class);

        $this->serializer = new CountrySerializer($this->countryRepository);
        $this->serializer->setRegistry($serializerRegistry);
    }

    public function testSimple(): void
    {
        $this->createCountry();

        $config = new Config([], [], []);
        $country = [
            'iso' => 'XX',
        ];

        $serialized = iterator_to_array($this->serializer->serialize($config, $this->countryRepository->getDefinition(), $country));

        $deserialized = iterator_to_array($this->serializer->deserialize($config, $this->countryRepository->getDefinition(), $serialized));

        static::assertSame($this->countryId, $deserialized['id']);
    }

    public function testSupportsOnlyCountry(): void
    {
        $serializer = new CountrySerializer(static::getContainer()->get('country.repository'));

        $definitionRegistry = static::getContainer()->get(DefinitionInstanceRegistry::class);
        foreach ($definitionRegistry->getDefinitions() as $definition) {
            $entity = $definition->getEntityName();

            if ($entity === CountryDefinition::ENTITY_NAME) {
                static::assertTrue($serializer->supports($entity));
            } else {
                static::assertFalse(
                    $serializer->supports($entity),
                    CountryDefinition::class . ' should not support ' . $entity
                );
            }
        }
    }

    private function createCountry(): void
    {
        $this->countryRepository->upsert([
            [
                'id' => $this->countryId,
                'iso' => 'XX',
                'name' => 'Test',
            ],
        ], Context::createDefaultContext());
    }
}
