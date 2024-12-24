<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Api;

use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResult;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Kernel;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('skip-paratest')]
class ApiAliasTest extends TestCase
{
    use KernelTestBehaviour;

    public function testUniqueAliases(): void
    {
        $classLoader = KernelLifecycleManager::getClassLoader();
        /** @var list<class-string> $classes */
        $classes = array_keys($classLoader->getClassMap());

        if (!\array_key_exists(Kernel::class, $classes)) {
            static::markTestSkipped('This test does not work if the root package is cicada-ag/platform');
        }

        $entities = self::getContainer()->get(DefinitionInstanceRegistry::class)
            ->getDefinitions();

        $aliases = array_keys($entities);
        $aliases = array_flip($aliases);

        $count = \count($aliases);

        foreach ($classes as $class) {
            $parts = explode('\\', $class);
            if ($parts[0] !== 'Cicada') {
                continue;
            }

            /** @phpstan-ignore-next-line class-string could not be resolved at this point */
            $reflector = new \ReflectionClass($class);

            if (!$reflector->isSubclassOf(Struct::class)) {
                continue;
            }

            if ($reflector->isAbstract() || $reflector->isInterface() || $reflector->isTrait()) {
                continue;
            }

            if ($reflector->isSubclassOf(AggregationResult::class)) {
                continue;
            }

            /** @phpstan-ignore-next-line PHPStan could not resolve the return type, due to the ignored error above */
            $instance = $reflector->newInstanceWithoutConstructor();

            if ($instance instanceof Entity) {
                continue;
            }

            if (!$instance instanceof Struct) {
                continue;
            }

            $alias = $instance->getApiAlias();

            if ($alias === 'aggregation-' || $alias === 'dal_entity_search_result') {
                continue;
            }

            static::assertArrayNotHasKey($alias, $aliases);
            $aliases[$alias] = true;
        }

        static::assertTrue(\count($aliases) > $count, 'Validated only entities, please check registered classes of class loader');
    }
}
