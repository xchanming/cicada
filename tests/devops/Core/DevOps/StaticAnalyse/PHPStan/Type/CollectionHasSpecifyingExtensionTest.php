<?php declare(strict_types=1);

namespace Cicada\Tests\DevOps\Core\DevOps\StaticAnalyse\PHPStan\Type;

use Cicada\Core\DevOps\StaticAnalyze\PHPStan\Type\CollectionHasSpecifyingExtension;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * @internal
 */
#[CoversClass(CollectionHasSpecifyingExtension::class)]
class CollectionHasSpecifyingExtensionTest extends TypeInferenceTestCase
{
    #[RunInSeparateProcess]
    public function testCollectionHas(): void
    {
        foreach (static::gatherAssertTypes(__DIR__ . '/data/collection_has.php') as $args) {
            // because of the autoload issue we can not use data providers as phpstan does itself,
            // therefore we need to rely on this hacks
            $assertType = array_shift($args);
            $file = array_shift($args);

            $this->assertFileAsserts($assertType, $file, ...$args);
        }
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/data/extension.neon',
        ];
    }
}
