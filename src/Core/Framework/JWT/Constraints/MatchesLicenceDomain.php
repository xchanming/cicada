<?php declare(strict_types=1);

namespace Cicada\Core\Framework\JWT\Constraints;

use Cicada\Core\Framework\JWT\JWTException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\InAppPurchase\Services\DecodedPurchasesCollectionStruct;
use Cicada\Core\Framework\Store\Services\StoreService;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;

#[Package('checkout')]
final readonly class MatchesLicenceDomain implements Constraint
{
    public function __construct(
        private SystemConfigService $systemConfigService
    ) {
    }

    public function assert(Token $token): void
    {
        $domain = $this->systemConfigService->get(StoreService::CONFIG_KEY_STORE_LICENSE_DOMAIN);

        if (!$domain) {
            throw JWTException::missingDomain();
        }

        if (!$token instanceof UnencryptedToken) {
            throw JWTException::invalidJwt('Incorrect token type');
        }

        $purchases = DecodedPurchasesCollectionStruct::fromArray($token->claims()->all());

        $firstPurchase = $purchases->first();
        if (!$firstPurchase) {
            throw JWTException::invalidJwt('No purchases found in JWT');
        }

        if ($firstPurchase->sub !== $domain) {
            throw JWTException::invalidDomain($firstPurchase->sub);
        }
    }
}
