<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\OAuth\Client;

use Cicada\Core\Framework\Log\Package;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;

#[Package('core')]
class ApiClient implements ClientEntityInterface
{
    use ClientTrait;

    public function __construct(
        private readonly string $identifier,
        private readonly bool $writeAccess,
        string $name = ''
    ) {
        $this->name = $name;
    }

    public function getWriteAccess(): bool
    {
        return $this->writeAccess;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function isConfidential(): bool
    {
        return true;
    }
}
