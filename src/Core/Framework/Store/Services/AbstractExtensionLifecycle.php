<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Services;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
abstract class AbstractExtensionLifecycle
{
    abstract public function install(string $type, string $technicalName, Context $context): void;

    abstract public function update(string $type, string $technicalName, bool $allowNewPermissions, Context $context): void;

    abstract public function uninstall(string $type, string $technicalName, bool $keepUserData, Context $context): void;

    abstract public function activate(string $type, string $technicalName, Context $context): void;

    abstract public function deactivate(string $type, string $technicalName, Context $context): void;

    abstract public function remove(string $type, string $technicalName, bool $keepUserData, Context $context): void;

    abstract protected function getDecorated(): AbstractExtensionLifecycle;
}
