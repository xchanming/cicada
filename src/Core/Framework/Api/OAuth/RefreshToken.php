<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\OAuth;

use Cicada\Core\Framework\Log\Package;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;

#[Package('core')]
class RefreshToken implements RefreshTokenEntityInterface
{
    use EntityTrait;
    use RefreshTokenTrait;
}
