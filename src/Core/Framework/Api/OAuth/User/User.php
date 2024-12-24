<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\OAuth\User;

use League\OAuth2\Server\Entities\UserEntityInterface;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class User implements UserEntityInterface
{
    public function __construct(private readonly string $userId)
    {
    }

    /**
     * Return the user's identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->userId;
    }
}
