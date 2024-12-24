<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Adapter\Twig;

use Cicada\Core\Framework\Adapter\Twig\AppTemplateIterator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AppTemplateIteratorTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testGetIterator(): void
    {
        // somehow the constructor is not marked as covered if we get the service from DI
        $iterator = new AppTemplateIterator(
            static::getContainer()->get('twig.template_iterator'),
            static::getContainer()->get('app_template.repository')
        );

        static::assertInstanceOf(\Generator::class, $iterator->getIterator());
    }

    public function testItAddsAppDatabaseTemplates(): void
    {
        /** @var EntityRepository $appRepository */
        $appRepository = static::getContainer()->get('app.repository');

        $appRepository->create([
            [
                'name' => 'SwagApp',
                'path' => __DIR__ . '/Manifest/_fixtures/test',
                'version' => '0.0.1',
                'label' => 'test',
                'accessToken' => 'test',
                'integration' => [
                    'label' => 'test',
                    'accessKey' => 'test',
                    'secretAccessKey' => 'test',
                ],
                'aclRole' => [
                    'name' => 'SwagApp',
                ],
                'templates' => [
                    [
                        'template' => 'test',
                        'path' => 'storefront/test/base.html.twig',
                        'active' => true,
                    ],
                    [
                        'template' => 'test',
                        'path' => 'storefront/test/active.html.twig',
                        'active' => true,
                    ],
                    [
                        'template' => 'test',
                        'path' => 'storefront/test/deactive.html.twig',
                        'active' => false,
                    ],
                ],
            ],
        ], Context::createDefaultContext());

        $templateIterator = static::getContainer()->get(AppTemplateIterator::class);

        $templates = iterator_to_array($templateIterator);

        static::assertContains('storefront/test/base.html.twig', $templates);
        static::assertContains('storefront/test/active.html.twig', $templates);
        static::assertNotContains('storefront/test/deactive.html.twig', $templates);
    }
}
