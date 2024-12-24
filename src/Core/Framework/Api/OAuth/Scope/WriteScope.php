<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\OAuth\Scope;

use Cicada\Core\Framework\Log\Package;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

#[Package('core')]
class WriteScope implements ScopeEntityInterface
{
    final public const IDENTIFIER = 'write';

    /**
     * Get the scope's identifier.
     */
    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function jsonSerialize(): mixed
    {
        return self::IDENTIFIER;
    }
}
