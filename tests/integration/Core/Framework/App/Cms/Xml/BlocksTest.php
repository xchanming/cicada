<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Cms\Xml;

use Cicada\Core\Framework\App\Cms\CmsExtensions;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class BlocksTest extends TestCase
{
    public function testFromXmlWithBlocks(): void
    {
        $cmsExtensions = CmsExtensions::createFromXmlFile(__DIR__ . '/../_fixtures/valid/cmsExtensionsWithBlocks.xml');

        static::assertNotNull($cmsExtensions->getBlocks());
        static::assertCount(2, $cmsExtensions->getBlocks()->getBlocks());

        $firstBlock = $cmsExtensions->getBlocks()->getBlocks()[0];
        static::assertSame('first-block-name', $firstBlock->getName());
        static::assertSame('text-image', $firstBlock->getCategory());
        static::assertEquals(
            [
                'en-GB' => 'First block from app',
                'zh-CN' => 'Erster Block einer App',
            ],
            $firstBlock->getLabel()
        );
    }

    public function testFromXmlWithoutBlocks(): void
    {
        $cmsExtensions = CmsExtensions::createFromXmlFile(__DIR__ . '/../_fixtures/valid/cmsExtensionsWithoutBlocks.xml');

        static::assertNull($cmsExtensions->getBlocks());
    }
}
