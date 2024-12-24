<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Lifecycle;

use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
abstract class AbstractAppLifecycle
{
    abstract public function getDecorated(): AbstractAppLifecycle;

    abstract public function install(Manifest $manifest, bool $activate, Context $context): void;

    /**
     * @param array{id: string, roleId: string} $app
     */
    abstract public function update(Manifest $manifest, array $app, Context $context): void;

    /**
     * @param array{id: string} $app
     */
    abstract public function delete(string $appName, array $app, Context $context, bool $keepUserData = false): void;
}
