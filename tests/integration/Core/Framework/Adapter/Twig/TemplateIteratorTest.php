<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Adapter\Twig;

use Cicada\Core\Framework\Adapter\Twig\TemplateIterator;
use Cicada\Core\Framework\Bundle;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class TemplateIteratorTest extends TestCase
{
    use KernelTestBehaviour;

    private TemplateIterator $iterator;

    protected function setUp(): void
    {
        $this->iterator = static::getContainer()->get(TemplateIterator::class);
    }

    public function testIteratorDoesNotFullPath(): void
    {
        $templateList = iterator_to_array($this->iterator, false);
        $bundles = static::getContainer()->getParameter('kernel.bundles');
        $cicadaBundles = [];

        foreach ($bundles as $bundleName => $bundleClass) {
            if (isset(class_parents($bundleClass)[Bundle::class])) {
                $cicadaBundles[] = '@' . $bundleName . '/';
            }
        }

        foreach ($cicadaBundles as $cicadaBundle) {
            foreach ($templateList as $template) {
                static::assertStringNotContainsStringIgnoringCase($cicadaBundle, $template);
            }
        }
    }
}
