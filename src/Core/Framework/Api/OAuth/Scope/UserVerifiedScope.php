<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\OAuth\Scope;

use Cicada\Core\Framework\Log\Package;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

#[Package('framework')]
class UserVerifiedScope implements ScopeEntityInterface
{
    final public const IDENTIFIER = 'user-verified';

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return self::IDENTIFIER;
    }

    public function jsonSerialize(): mixed
    {
        return self::IDENTIFIER;
    }
}
