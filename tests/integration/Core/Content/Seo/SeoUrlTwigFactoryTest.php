<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Seo;

use Cicada\Core\Content\Seo\SeoUrlGenerator;
use Cicada\Core\Content\Test\Seo\Twig\LastLetterBigTwigFilter;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

/**
 * @internal
 */
class SeoUrlTwigFactoryTest extends TestCase
{
    use KernelTestBehaviour;

    private Environment $environment;

    protected function setUp(): void
    {
        $this->environment = static::getContainer()->get('cicada.seo_url.twig');
    }

    public function testLoadAdditionalExtension(): void
    {
        // extension loaded via custom tag in src/Core/Framework/DependencyInjection/seo_test.xml
        static::assertInstanceOf(LastLetterBigTwigFilter::class, $this->environment->getExtension(LastLetterBigTwigFilter::class));

        $template = '{% autoescape \''
            . SeoUrlGenerator::ESCAPE_SLUGIFY
            . '\' %}{{ product.name|lastBigLetter }}{% endautoescape %}';

        $twig = $this->environment->createTemplate($template);
        $rendered = $twig->render(['product' => ['name' => 'hello world']]);

        static::assertSame('hello-worlD', $rendered);
    }
}
