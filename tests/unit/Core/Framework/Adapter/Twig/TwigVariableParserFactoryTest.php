<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Twig;

use Cicada\Core\Framework\Adapter\Twig\TwigVariableParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * @internal
 */
#[CoversClass(TwigVariableParserFactory::class)]
class TwigVariableParserFactoryTest extends TestCase
{
    public function testGetParser(): void
    {
        $factory = new TwigVariableParserFactory();
        $twig = new Environment(new ArrayLoader([]));

        $parser = $factory->getParser($twig);
        static::assertSame([], $parser->parse('123-test'));
    }
}
