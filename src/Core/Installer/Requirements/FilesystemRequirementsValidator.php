<?php declare(strict_types=1);

namespace Cicada\Core\Installer\Requirements;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Installer\Requirements\Struct\PathCheck;
use Cicada\Core\Installer\Requirements\Struct\RequirementCheck;
use Cicada\Core\Installer\Requirements\Struct\RequirementsCheckCollection;

/**
 * @internal
 */
#[Package('framework')]
class FilesystemRequirementsValidator implements RequirementsValidatorInterface
{
    private const NEEDED_PATHS = [
        '.',
        'var/log/',
        'var/cache/',
        'public/',
        'config/jwt/',
    ];

    public function __construct(private readonly string $projectDir)
    {
    }

    public function validateRequirements(RequirementsCheckCollection $checks): RequirementsCheckCollection
    {
        foreach (self::NEEDED_PATHS as $path) {
            $absolutePath = $this->projectDir . '/' . $path;

            $checks->add(new PathCheck(
                $path,
                $this->existsAndIsWritable($absolutePath) ? RequirementCheck::STATUS_SUCCESS : RequirementCheck::STATUS_ERROR
            ));
        }

        return $checks;
    }

    private function existsAndIsWritable(string $path): bool
    {
        return file_exists($path) && is_readable($path) && is_writable($path);
    }
}
