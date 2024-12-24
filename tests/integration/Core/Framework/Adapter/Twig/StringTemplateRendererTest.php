<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Adapter\Twig;

use Cicada\Core\Framework\Adapter\Twig\StringTemplateRenderer;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use PHPUnit\Framework\TestCase;
use Twig\Extension\CoreExtension;

/**
 * @internal
 */
class StringTemplateRendererTest extends TestCase
{
    use KernelTestBehaviour;

    private StringTemplateRenderer $stringTemplateRenderer;

    protected function setUp(): void
    {
        $this->stringTemplateRenderer = static::getContainer()->get(StringTemplateRenderer::class);
    }

    public function testRender(): void
    {
        $templateMock = '{{ foo }}';
        $dataMock = ['foo' => 'bar'];
        $rendered = $this->stringTemplateRenderer->render($templateMock, $dataMock, Context::createDefaultContext());
        static::assertEquals('bar', $rendered);
    }

    public function testInitialization(): void
    {
        $templateMock = '{{ testDate|format_date(pattern="HH:mm") }}';
        $testDate = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $context = Context::createDefaultContext();

        /** @var CoreExtension $coreExtension */
        $coreExtension = static::getContainer()->get('twig')->getExtension(CoreExtension::class);
        $coreExtension->setTimezone('Europe/London');
        $this->stringTemplateRenderer->initialize();
        $renderedTime = $this->stringTemplateRenderer->render($templateMock, ['testDate' => $testDate], $context);

        /** @var CoreExtension $coreExtension */
        $coreExtension = static::getContainer()->get('twig')->getExtension(CoreExtension::class);
        $coreExtension->setTimezone('Europe/Berlin');
        $this->stringTemplateRenderer->initialize();

        $renderedWithTimezone = $this->stringTemplateRenderer->render($templateMock, ['testDate' => $testDate], $context);

        static::assertNotEquals($renderedTime, $renderedWithTimezone);
    }
}
