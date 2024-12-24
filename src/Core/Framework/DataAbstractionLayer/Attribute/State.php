<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Attribute;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class State extends Field
{
    public const TYPE = 'state';

    /**
     * @param array<string> $scopes
     */
    public function __construct(
        public string $machine,
        public array $scopes = [Context::SYSTEM_SCOPE],
        bool|array $api = false,
        ?string $column = null
    ) {
        parent::__construct(type: self::TYPE, api: $api, column: $column);
    }
}
