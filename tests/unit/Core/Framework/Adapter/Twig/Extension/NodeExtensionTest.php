<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Twig\Extension;

use Cicada\Core\Framework\Adapter\Twig\Extension\NodeExtension;
use Cicada\Core\Framework\Adapter\Twig\TemplateFinder;
use Cicada\Core\Framework\Adapter\Twig\TemplateScopeDetector;
use Cicada\Core\Framework\Adapter\Twig\TokenParser\ExtendsTokenParser;
use Cicada\Core\Framework\Adapter\Twig\TokenParser\IncludeTokenParser;
use Cicada\Core\Framework\Adapter\Twig\TokenParser\ReturnNodeTokenParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\TokenParser\TokenParserInterface;

/**
 * @internal
 */
#[CoversClass(NodeExtension::class)]
class NodeExtensionTest extends TestCase
{
    public function testGetTokenParsers(): void
    {
        $extension = new NodeExtension(
            $this->createMock(TemplateFinder::class),
            $this->createMock(TemplateScopeDetector::class),
        );
        static::assertCount(3, $extension->getTokenParsers());
        static::assertSame([
            ExtendsTokenParser::class,
            IncludeTokenParser::class,
            ReturnNodeTokenParser::class,
        ], array_map(fn (TokenParserInterface $parser) => $parser::class, $extension->getTokenParsers()));
    }

    public function testGetFinder(): void
    {
        $finder = $this->createMock(TemplateFinder::class);
        $extension = new NodeExtension(
            $finder,
            $this->createMock(TemplateScopeDetector::class),
        );
        static::assertSame($finder, $extension->getFinder());
    }

    public function testEmptyExtensions(): void
    {
        $extension = new NodeExtension(
            $this->createMock(TemplateFinder::class),
            $this->createMock(TemplateScopeDetector::class),
        );

        static::assertEmpty($extension->getFunctions());
        static::assertEmpty($extension->getFilters());
        static::assertEmpty($extension->getNodeVisitors());
        static::assertEmpty($extension->getTests());
    }
}
