<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Script\Debugging;

use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
class Debug
{
    protected array $dumps = [];

    public function dump(mixed $value, ?string $key = null): void
    {
        if ($key !== null) {
            $this->dumps[$key] = $value;
        } else {
            $this->dumps[] = $value;
        }
    }

    public function all(): array
    {
        return $this->dumps;
    }
}
