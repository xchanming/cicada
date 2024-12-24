<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationCollectionLoader;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(MigrationCollectionLoader::class)]
class DateTimeTest extends TestCase
{
    use KernelTestBehaviour;

    public function testMigrationDoesntUseDate(): void
    {
        $errorTemplate = <<<'EOF'
Attention: date(Defaults::(STORAGE_DATE_TIME_FORMAT|STORAGE_DATE_FORMAT)) has been used in "%s".
Please be aware that date doesn't support microseconds and is therefore incompatible with our default datetime format.
Please use (new \DateTime())->format(STORAGE_DATE_TIME_FORMAT) instead.
EOF;

        $classLoader = KernelLifecycleManager::getClassLoader();

        $migrationLoader = static::getContainer()->get(MigrationCollectionLoader::class);
        foreach ($migrationLoader->collectAll() as $collection) {
            foreach (array_keys($collection->getMigrationSteps()) as $className) {
                /** @var string $file */
                $file = $classLoader->findFile($className);

                $result = preg_match_all(
                    '/date\(Defaults::(STORAGE_DATE_TIME_FORMAT|STORAGE_DATE_FORMAT).*\);/',
                    (string) file_get_contents($file),
                    $matches
                );

                static::assertSame(0, $result, \sprintf($errorTemplate, basename($file)));
            }
        }
    }
}
