<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Changelog;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @internal
 *
 * @extends Collection<ChangelogFile>
 */
#[Package('framework')]
class ChangelogFileCollection extends Collection
{
    protected function getExpectedClass(): ?string
    {
        return ChangelogFile::class;
    }
}
