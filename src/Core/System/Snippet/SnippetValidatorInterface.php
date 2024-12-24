<?php declare(strict_types=1);

namespace Cicada\Core\System\Snippet;

use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
interface SnippetValidatorInterface
{
    public function validate(): array;
}
