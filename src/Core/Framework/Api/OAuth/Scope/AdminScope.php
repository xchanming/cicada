<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\OAuth\Scope;

use Cicada\Core\Framework\Log\Package;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

#[Package('core')]
class AdminScope implements ScopeEntityInterface
{
    final public const IDENTIFIER = 'admin';

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
