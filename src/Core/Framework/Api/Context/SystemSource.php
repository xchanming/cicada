<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\Context;

use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
class SystemSource implements ContextSource
{
    public string $type = 'system';
}
