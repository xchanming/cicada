<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\MyFakeNamespace;

class DomainExceptionViolations
{
    public function throwRuntimeException(): void
    {
        throw new \RuntimeException('This is a runtime exception');
    }
}
