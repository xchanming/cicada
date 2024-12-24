<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Store;

use Cicada\Core\Framework\App\Lifecycle\AppLifecycle;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
trait ServiceBehaviour
{
    use ExtensionBehaviour;

    public function installService(string $path, bool $install = true): void
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

        $manifest = Manifest::createFromXmlFile($appDir . '/manifest.xml');
        $manifest->getMetadata()->setSelfManaged(true);

        if ($install) {
            static::getContainer()->get(AppLifecycle::class)->install($manifest, true, Context::createDefaultContext());
        }
    }
}
