<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Update\Checkers;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Update\Services\Filesystem;
use Cicada\Core\Framework\Update\Struct\ValidationResult;

#[Package('services-settings')]
class WriteableCheck
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly string $rootDir
    ) {
    }

    public function check(): ValidationResult
    {
        $directories = [];
        $checkedDirectories = [];

        $fullPath = rtrim($this->rootDir . '/');
        $checkedDirectories[] = $fullPath;

        $directories = array_merge(
            $directories,
            $this->filesystem->checkSingleDirectoryPermissions($fullPath, true)
        );

        if (empty($directories)) {
            return new ValidationResult(
                'writeableCheck',
                true,
                'writeableCheckValid',
                ['checkedDirectories' => implode('<br>', $checkedDirectories)]
            );
        }

        return new ValidationResult(
            'writeableCheck',
            false,
            'writeableCheckFailed',
            ['failedDirectories' => implode('<br>', $directories)]
        );
    }
}
