<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity;

use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity\LanguageSerializer;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Language\LanguageDefinition;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class LanguageSerializerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $languageRepository;

    private LanguageSerializer $serializer;

    private string $languageId = '1a9e90835a634ffd900b5a441251f551';

    protected function setUp(): void
    {
        $this->languageRepository = static::getContainer()->get('language.repository');
        $serializerRegistry = static::getContainer()->get(SerializerRegistry::class);

        $this->serializer = new LanguageSerializer($this->languageRepository);
        $this->serializer->setRegistry($serializerRegistry);
    }

    public function testSimple(): void
    {
        $this->createCountry();

        $config = new Config([], [], []);
        $language = [
            'locale' => [
                'code' => 'xx-XX',
            ],
        ];

        $serialized = iterator_to_array($this->serializer->serialize($config, $this->languageRepository->getDefinition(), $language));

        $deserialized = iterator_to_array($this->serializer->deserialize($config, $this->languageRepository->getDefinition(), $serialized));

        static::assertSame($this->languageId, $deserialized['id']);
    }

    public function testSupportsOnlyCountry(): void
    {
        $serializer = new LanguageSerializer(static::getContainer()->get('language.repository'));

        $definitionRegistry = static::getContainer()->get(DefinitionInstanceRegistry::class);
        foreach ($definitionRegistry->getDefinitions() as $definition) {
            $entity = $definition->getEntityName();

            if ($entity === LanguageDefinition::ENTITY_NAME) {
                static::assertTrue($serializer->supports($entity));
            } else {
                static::assertFalse(
                    $serializer->supports($entity),
                    LanguageDefinition::class . ' should not support ' . $entity
                );
            }
        }
    }

    private function createCountry(): void
    {
        $localeId = Uuid::randomHex();
        $this->languageRepository->upsert([
            [
                'id' => $this->languageId,
                'name' => 'test name',
                'locale' => [
                    'id' => $localeId,
                    'code' => 'xx-XX',
                    'name' => 'test name',
                    'territory' => 'test territory',
                ],
                'translationCodeId' => $localeId,
            ],
        ], Context::createDefaultContext());
    }
}
