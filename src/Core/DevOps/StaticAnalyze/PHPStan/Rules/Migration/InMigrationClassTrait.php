<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules\Migration;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use PHPStan\Analyser\Scope;

/**
 * @internal
 */
#[Package('core')]
trait InMigrationClassTrait
{
    protected function isInMigrationClass(Scope $scope): bool
    {
        if (!$scope->isInClass()) {
            return false;
        }

        return $scope->getClassReflection()->isSubclassOf(MigrationStep::class);
    }
}
