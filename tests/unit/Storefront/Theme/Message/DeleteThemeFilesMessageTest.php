<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme\Message;

use Cicada\Storefront\Theme\Message\DeleteThemeFilesMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DeleteThemeFilesMessage::class)]
class DeleteThemeFilesMessageTest extends TestCase
{
    public function testStruct(): void
    {
        $message = new DeleteThemeFilesMessage('path', 'salesChannel', 'theme');

        static::assertEquals('path', $message->getThemePath());
        static::assertEquals('salesChannel', $message->getSalesChannelId());
        static::assertEquals('theme', $message->getThemeId());
    }
}
