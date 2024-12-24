<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Api\ApiDefinition\Generator;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Api\ApiDefinition\Generator\EntitySchemaGenerator;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Tests\Integration\Core\Framework\Api\ApiDefinition\EntityDefinition\SimpleDefinition;

/**
 * @internal
 */
final class EntitySchemaGeneratorTest extends TestCase
{
    use KernelTestBehaviour;

    public function testAllEntriesHaveProtectionHints(): void
    {
        $definitionRegistry = new DefinitionInstanceRegistry(
            self::getContainer(),
            ['simple' => SimpleDefinition::class],
            ['simple' => 'simple.repository']
        );
        $definitionRegistry->register(new SimpleDefinition(), 'simple');

        $definitions = (new EntitySchemaGenerator())->getSchema($definitionRegistry->getDefinitions());

        static::assertNotEmpty($definitions);

        foreach ($definitions as $definition) {
            static::assertArrayHasKey('write-protected', $definition);
            static::assertArrayHasKey('read-protected', $definition);
        }
    }

    public function testNoEntriesHaveBothProtectionHintsTrue(): void
    {
        $definitionRegistry = new DefinitionInstanceRegistry(
            static::getContainer(),
            ['simple' => SimpleDefinition::class],
            ['simple' => 'simple.repository']
        );
        $definitionRegistry->register(new SimpleDefinition(), 'simple');

        $definitions = (new EntitySchemaGenerator())->getSchema($definitionRegistry->getDefinitions());

        foreach ($definitions as $definition) {
            static::assertFalse($definition['write-protected'] && $definition['read-protected']);
        }
    }
}
