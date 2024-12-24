<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Service\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Service\Message\UpdateServiceMessage;

/**
 * @internal
 */
#[CoversClass(UpdateServiceMessage::class)]
class UpdateServiceMessageTest extends TestCase
{
    public function testMeta(): void
    {
        $message = new UpdateServiceMessage('MyCoolService');

        static::assertSame('MyCoolService', $message->name);
    }
}
