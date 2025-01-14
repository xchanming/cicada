<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Twig\Filter;

use Cicada\Core\Framework\Adapter\Twig\Filter\ReplaceRecursiveFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * @internal
 */
#[CoversClass(ReplaceRecursiveFilter::class)]
class ReplaceRecursiveFilterTest extends TestCase
{
    public function testReplace(): void
    {
        $env = new Environment(new ArrayLoader(['test' => '{{ {"berries": ["blueberry"]}|replace_recursive({"berries": ["strawberry", "blackberry"]})|json_encode|raw }}']));
        $env->addExtension(new ReplaceRecursiveFilter());

        static::assertSame('{"berries":["strawberry","blackberry"]}', $env->render('test'));
    }
}
