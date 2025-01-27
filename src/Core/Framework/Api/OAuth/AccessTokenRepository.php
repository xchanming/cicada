<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\OAuth;

use Cicada\Core\Framework\Api\OAuth\Client\ApiClient;
use Cicada\Core\Framework\Log\Package;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

#[Package('framework')]
class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null): AccessTokenEntityInterface
    {
        $token = new AccessToken($clientEntity, $scopes, $userIdentifier);

        if ($clientEntity instanceof ApiClient && $clientEntity->getIdentifier() === 'administration') {
            $token->setIdentifier('administration');
        }

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessToken($tokenId): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isAccessTokenRevoked($tokenId): bool
    {
        return false;
    }
}
