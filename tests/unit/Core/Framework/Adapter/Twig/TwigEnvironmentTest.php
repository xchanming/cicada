<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Twig;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Adapter\Twig\TwigEnvironment;
use Twig\Loader\ArrayLoader;
use Twig\Source;

/**
 * @internal
 */
#[CoversClass(TwigEnvironment::class)]
class TwigEnvironmentTest extends TestCase
{
    public function testUsesCicadaFunctions(): void
    {
        $twig = new TwigEnvironment(new ArrayLoader(['bla' => '{{ test.bla }}']));

        $code = $twig->compileSource(new Source('{{ test.bla }}', 'bla'));

        static::assertStringContainsString('use Cicada\Core\Framework\Adapter\Twig\SwTwigFunction;', $code);
        static::assertStringContainsString('SwTwigFunction::getAttribute', $code);
    }
}
