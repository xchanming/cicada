<?php declare(strict_types=1);

namespace Cicada\Core\System\Snippet;

use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
interface SnippetValidatorInterface
{
    public function validate(): array;
}
