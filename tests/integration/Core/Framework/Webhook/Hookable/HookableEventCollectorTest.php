<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Webhook\Hookable;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Webhook\Hookable\HookableEventCollector;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class HookableEventCollectorTest extends TestCase
{
    use IntegrationTestBehaviour;

    private HookableEventCollector $hookableEventCollector;

    protected function setUp(): void
    {
        $this->hookableEventCollector = static::getContainer()->get(HookableEventCollector::class);
    }

    public function testGetHookableEventNamesWithPrivileges(): void
    {
        $hookableEventNamesWithPrivileges = $this->hookableEventCollector->getHookableEventNamesWithPrivileges(Context::createDefaultContext());
        static::assertNotEmpty($hookableEventNamesWithPrivileges);

        foreach ($hookableEventNamesWithPrivileges as $key => $hookableEventNamesWithPrivilege) {
            static::assertIsArray($hookableEventNamesWithPrivilege);
            static::assertIsString($key);
            static::assertArrayHasKey('privileges', $hookableEventNamesWithPrivilege);
        }
    }
}
