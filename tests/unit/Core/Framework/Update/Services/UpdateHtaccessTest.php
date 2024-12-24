<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Update\Services;

use Cicada\Core\Framework\Update\Services\UpdateHtaccess;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(UpdateHtaccess::class)]
class UpdateHtaccessTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        static::assertSame(
            [
                'Cicada\Core\Framework\Update\Event\UpdatePostFinishEvent' => 'update',
            ],
            UpdateHtaccess::getSubscribedEvents()
        );
    }

    #[DataProvider('getCombinations')]
    public function testCombination(string $currentEnv, ?string $newEnv, string $expected): void
    {
        $fs = sys_get_temp_dir() . '/' . uniqid(__METHOD__, true) . '/';
        mkdir($fs);

        file_put_contents($fs . '.env', $currentEnv);

        if ($newEnv) {
            file_put_contents($fs . '.env.dist', $newEnv);
        }

        $updater = new UpdateHtaccess($fs . '.env');
        $updater->update();

        static::assertSame($expected, file_get_contents($fs . '.env'));
    }

    /**
     * @return iterable<array-key, array{string, ?string, string}>
     */
    public static function getCombinations(): iterable
    {
        // Dist file missing
        yield [
            'Test',
            null,
            'Test',
        ];

        // User has removed marker
        yield [
            'Test',
            '# BEGIN Cicada
Test
# END Cicada',
            'Test',
        ];

        // Update marker
        yield [
            '# BEGIN Cicada
OLD
# END Cicada',
            '# BEGIN Cicada
NEW
# END Cicada',
            '# BEGIN Cicada
# The directives (lines) between "# BEGIN Cicada" and "# END Cicada" are dynamically generated. Any changes to the directives between these markers will be overwritten.
NEW
# END Cicada',
        ];

        // Update marker with pre and after lines
        yield [
            'BEFORE
# BEGIN Cicada
OLD
# END Cicada
AFTER',
            '# BEGIN Cicada
NEW
# END Cicada',
            'BEFORE
# BEGIN Cicada
# The directives (lines) between "# BEGIN Cicada" and "# END Cicada" are dynamically generated. Any changes to the directives between these markers will be overwritten.
NEW
# END Cicada
AFTER',
        ];

        // Update containg help text
        yield [
            'BEFORE
# BEGIN Cicada
# The directives (lines) between "# BEGIN Cicada" and "# END Cicada" are dynamically generated. Any changes to the directives between these markers will be overwritten.
OLD
# END Cicada
AFTER',
            '# BEGIN Cicada
# The directives (lines) between "# BEGIN Cicada" and "# END Cicada" are dynamically generated. Any changes to the directives between these markers will be overwritten.
NEW
# END Cicada',
            'BEFORE
# BEGIN Cicada
# The directives (lines) between "# BEGIN Cicada" and "# END Cicada" are dynamically generated. Any changes to the directives between these markers will be overwritten.
NEW
# END Cicada
AFTER',
        ];
    }

    public function testReplaceComplete(): void
    {
        $fs = sys_get_temp_dir() . '/' . uniqid(__METHOD__, true) . '/';
        mkdir($fs);

        copy(__DIR__ . '/../_fixtures/htaccess', $fs . '.htaccess');
        $newHtaccess = '# BEGIN Cicada
# The directives (lines) between "# BEGIN Cicada" and "# END Cicada" are dynamically generated. Any changes to the directives between these markers will be overwritten.
NEW
# END Cicada';
        file_put_contents($fs . '.htaccess.dist', $newHtaccess);

        $updater = new UpdateHtaccess($fs . '.htaccess');
        $updater->update();

        static::assertSame($newHtaccess, file_get_contents($fs . '.htaccess'));
    }
}
