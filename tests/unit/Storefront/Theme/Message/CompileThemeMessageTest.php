<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme\Message;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Theme\Message\CompileThemeMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CompileThemeMessage::class)]
class CompileThemeMessageTest extends TestCase
{
    public function testStruct(): void
    {
        $themeId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $message = new CompileThemeMessage(TestDefaults::SALES_CHANNEL, $themeId, true, $context);

        static::assertEquals($themeId, $message->getThemeId());
        static::assertEquals(TestDefaults::SALES_CHANNEL, $message->getSalesChannelId());
        static::assertTrue($message->isWithAssets());
        static::assertEquals($context, $message->getContext());
    }
}
