<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Adapter\Twig\Extension;

use Cicada\Core\Framework\Adapter\Twig\StringTemplateRenderer;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Struct\ArrayStruct;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class PhpSyntaxExtensionTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testSyntax(): void
    {
        $template = file_get_contents(__DIR__ . '/fixture/php-syntax-extension.html.twig');
        static::assertIsString($template);
        $renderer = static::getContainer()->get(StringTemplateRenderer::class);

        $jsonEncodeData = [
            -4,
            'foo' => 'bar',
            'Cicada/Code',
            'list' => [
                ['foo', 'bar'],
            ],
        ];

        $data = [
            'test' => 'test',
            'list' => [-4, 'foo', 'bar'],
            'trueValue' => true,
            'falseValue' => false,
            'stringValue' => 'string',
            'scalarValue' => 1,
            'objectValue' => new ArrayStruct(),
            'intValue' => 1,
            'floatValue' => 1.1,
            'callableValue' => function (): void {
            },
            'arrayValue' => [],
            'jsonEncode' => [
                'data' => $jsonEncodeData,
                'expected' => [
                    json_encode($jsonEncodeData),
                    json_encode($jsonEncodeData, \JSON_UNESCAPED_SLASHES),
                    json_encode($jsonEncodeData, \JSON_PRETTY_PRINT),
                    json_encode($jsonEncodeData, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES),
                ],
            ],
        ];

        $result = $renderer->render($template, $data, Context::createDefaultContext());

        $expected = '';
        for ($i = 1; $i <= 22; ++$i) {
            $expected .= '-' . $i;
        }
        foreach ($data['jsonEncode']['expected'] as $index => $any) {
            $expected .= '-jsonEncode' . $index;
        }

        static::assertEquals($expected, $result, 'Failure in php syntax support in twig rendering');
    }
}
