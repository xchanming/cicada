<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Source;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Util\Filesystem;

/**
 * @internal
 */
#[Package('core')]
interface Source
{
    public static function name(): string;

    public function supports(AppEntity|Manifest $app): bool;

    public function filesystem(AppEntity|Manifest $app): Filesystem;

    /**
     * @param array<Filesystem> $filesystems
     */
    public function reset(array $filesystems): void;
}
