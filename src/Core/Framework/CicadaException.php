<?php declare(strict_types=1);

namespace Cicada\Core\Framework;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
interface CicadaException extends \Throwable
{
    public function getErrorCode(): string;

    /**
     * @return array<string|int, mixed|null>
     */
    public function getParameters(): array;
}
