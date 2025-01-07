<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\File;

use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
interface FileUrlValidatorInterface
{
    public function isValid(string $source): bool;
}
