<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\User\Recovery;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Storer\ScalarValuesStorer;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\User\Aggregate\UserRecovery\UserRecoveryEntity;
use Cicada\Core\System\User\Recovery\UserRecoveryRequestEvent;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(UserRecoveryRequestEvent::class)]
class UserRecoveryRequestEventTest extends TestCase
{
    public function testScalarValuesCorrectly(): void
    {
        $event = new UserRecoveryRequestEvent(
            new UserRecoveryEntity(),
            'my-reset-url',
            Context::createDefaultContext(),
        );

        $storer = new ScalarValuesStorer();
        $stored = $storer->store($event, []);

        $flow = new StorableFlow('foo', Context::createDefaultContext(), $stored);

        $storer->restore($flow);

        static::assertArrayHasKey('resetUrl', $flow->data());
        static::assertEquals('my-reset-url', $flow->data()['resetUrl']);
    }
}
