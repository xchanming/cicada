<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Twig\Node;

use Cicada\Core\Framework\Adapter\Twig\Node\FeatureCallSilentToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\Compiler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\TextNode;

/**
 * @internal
 */
#[CoversClass(FeatureCallSilentToken::class)]
class FeatureCallSilentTokenTest extends TestCase
{
    public function testCompile(): void
    {
        $token = new FeatureCallSilentToken('v6.5.0.0', new TextNode('test', 1), 1);

        $compiler = new Compiler(new Environment(new ArrayLoader()));

        $compiler->compile($token);

        $code = <<<'PHP'
// line 1
\Cicada\Core\Framework\Feature::callSilentIfInactive("v6.5.0.0", function () use(&$context) { yield "test";
});
PHP;

        static::assertSame($code, $compiler->getSource());
    }
}
