<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\DataAbstractionLayer\AttributeMappingDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\AttributeTranslationDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @internal
 */
class EntityDefinitionHasSinceTest extends TestCase
{
    use KernelTestBehaviour;

    public function testAllDefinitionsHasSince(): void
    {
        $service = static::getContainer()->get(DefinitionInstanceRegistry::class);

        $definitionsWithoutSince = [];

        foreach ($service->getDefinitions() as $definition) {
            if ($definition instanceof AttributeMappingDefinition || $definition instanceof AttributeTranslationDefinition) {
                continue;
            }

            if ($definition->since() === null) {
                $definitionsWithoutSince[] = $definition->getEntityName();
            }
        }

        static::assertCount(0, $definitionsWithoutSince, \sprintf('Following definitions does not have a since version: %s', implode(',', $definitionsWithoutSince)));
    }
}
