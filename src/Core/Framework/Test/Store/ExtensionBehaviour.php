<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Store;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Store\Services\AbstractStoreAppLifecycleService;
use Symfony\Component\Filesystem\Filesystem;

trait ExtensionBehaviour
{
    public function installApp(string $path, bool $install = true): void
    {
        $appRepository = static::getContainer()->get('app.repository');
        $idResult = $appRepository->searchIds(new Criteria(), Context::createDefaultContext());

        /** @var array<string> $ids */
        $ids = $idResult->getIds();
        if (\count($ids)) {
            $appRepository->delete(array_map(fn (string $id) => ['id' => $id], $ids), Context::createDefaultContext());
        }

        $fs = new Filesystem();

        $name = basename($path);
        $appDir = static::getContainer()->getParameter('cicada.app_dir') . '/' . $name;
        $fs->mirror($path, $appDir);

        if ($install) {
            static::getContainer()->get(AbstractStoreAppLifecycleService::class)->installExtension($name, Context::createDefaultContext());
        }
    }

    public function removeApp(string $path): void
    {
        $fs = new Filesystem();

        $fs->remove(static::getContainer()->getParameter('cicada.app_dir') . '/' . basename($path));
    }

    public function registerPlugin(string $path): void
    {
        $fs = new Filesystem();

        $name = basename($path);
        $pluginDir = static::getContainer()->getParameter('kernel.plugin_dir') . '/' . $name;
        $fs->mirror($path, $pluginDir);
    }

    public function removePlugin(string $path): void
    {
        $fs = new Filesystem();

        $fs->remove(static::getContainer()->getParameter('kernel.plugin_dir') . '/' . basename($path));
    }
}
