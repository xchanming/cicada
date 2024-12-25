<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Installer\Controller;

use Cicada\Core\Kernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

/**
 * @internal
 */
trait InstallerControllerTestTrait
{
    /**
     * @param array<string, object> $services
     */
    private function getInstallerContainer(Environment $twig, array $services = []): ContainerInterface
    {
        $container = new ContainerBuilder();
        $container->set('twig', $twig);
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], ['_route' => 'installer.language-selection']));
        $container->set('request_stack', $requestStack);
        $container->setParameter('cicada.installer.supportedLanguages', ['en' => 'en-GB', 'de' => 'zh-CN']);
        $container->setParameter('kernel.cicada_version', Kernel::CICADA_FALLBACK_VERSION);

        foreach ($services as $id => $service) {
            $container->set($id, $service);
        }

        return $container;
    }

    /**
     * @return array{menu: array{label: string, active: bool, isCompleted: bool}[], supportedLanguages: string[], cicada: array{version: string}}
     */
    private function getDefaultViewParams(): array
    {
        return [
            'menu' => [
                [
                    'label' => 'language-selection',
                    'active' => true,
                    'isCompleted' => false,
                ],
                [
                    'label' => 'requirements',
                    'active' => false,
                    'isCompleted' => false,
                ],
                [
                    'label' => 'database-configuration',
                    'active' => false,
                    'isCompleted' => false,
                ],
                [
                    'label' => 'database-import',
                    'active' => false,
                    'isCompleted' => false,
                ],
                [
                    'label' => 'configuration',
                    'active' => false,
                    'isCompleted' => false,
                ],
                [
                    'label' => 'finish',
                    'active' => false,
                    'isCompleted' => false,
                ],
            ],
            'supportedLanguages' => ['en', 'de'],
            'cicada' => [
                'version' => Kernel::CICADA_FALLBACK_VERSION,
            ],
        ];
    }
}
