<?php declare(strict_types=1);

namespace Cicada\Tests\DevOps\Core\DevOps\StaticAnalyse\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules\CicadaNamespaceStyleRule;

/**
 * @internal
 *
 * @extends RuleTestCase<CicadaNamespaceStyleRule>
 */
#[CoversClass(CicadaNamespaceStyleRule::class)]
class CicadaNamespaceStyleRuleTest extends RuleTestCase
{
    #[RunInSeparateProcess]
    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/NamespaceStyle/AllCorrect.php'], []);

        $this->analyse([__DIR__ . '/data/NamespaceStyle/NoCicada.php'], [
            [
                'Namespace must start with Cicada',
                3,
            ],
        ]);

        $this->analyse([__DIR__ . '/data/NamespaceStyle/GlobalCommand.php'], [
            [
                'No global Command directories allowed, put your commands in the right domain directory',
                3,
            ],
        ]);

        $this->analyse([__DIR__ . '/data/NamespaceStyle/GlobalException.php'], [
            [
                'No global Exception directories allowed, put your exceptions in the right domain directory',
                3,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        return new CicadaNamespaceStyleRule();
    }
}
