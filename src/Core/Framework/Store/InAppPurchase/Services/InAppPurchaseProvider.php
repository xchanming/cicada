<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\InAppPurchase\Services;

use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\JWT\Constraints\HasValidRSAJWKSignature;
use Cicada\Core\Framework\JWT\Constraints\MatchesLicenceDomain;
use Cicada\Core\Framework\JWT\JWTDecoder;
use Cicada\Core\Framework\JWT\JWTException;
use Cicada\Core\Framework\JWT\Struct\JWKStruct;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SystemConfig\SystemConfigService;

/**
 * @internal
 *
 * @phpstan-import-type JSONWebKey from JWKStruct
 */
#[Package('checkout')]
final class InAppPurchaseProvider
{
    public const CONFIG_STORE_IAP_KEY = 'core.store.iapKey';

    public function __construct(
        private readonly SystemConfigService $systemConfig,
        private readonly JWTDecoder $decoder,
        private readonly KeyFetcher $keyFetcher
    ) {
    }

    /**
     * @return array<string, list<string>>
     */
    public function getPurchases(): array
    {
        $purchases = $this->systemConfig->getString(self::CONFIG_STORE_IAP_KEY);
        if (!$purchases) {
            return [];
        }

        $purchases = json_decode($purchases, true);
        if (!\is_array($purchases)) {
            return [];
        }

        return $this->filterActive($this->decodePurchases($purchases));
    }

    /**
     * @return array<string, string>
     */
    public function getPurchasesJWT(): array
    {
        $purchases = $this->systemConfig->getString(self::CONFIG_STORE_IAP_KEY);
        if (!$purchases) {
            return [];
        }

        return json_decode($purchases, true);
    }

    /**
     * @param array<string, string> $encodedPurchases
     *
     * @return array<string, array<int, DecodedPurchasesCollectionStruct>>
     */
    private function decodePurchases(array $encodedPurchases, bool $retried = false): array
    {
        if ($encodedPurchases === []) {
            return [];
        }
        $decodedPurchases = [];

        $context = Context::createDefaultContext();

        try {
            $jwks = $this->keyFetcher->getKey($context, $retried);
            $signatureValidator = new HasValidRSAJWKSignature($jwks);
            $domainValidator = new MatchesLicenceDomain($this->systemConfig);
            foreach ($encodedPurchases as $extensionName => $purchaseJwt) {
                $this->decoder->validate($purchaseJwt, $signatureValidator, $domainValidator);
                $decodedPurchases[$extensionName][] = DecodedPurchasesCollectionStruct::fromArray($this->decoder->decode($purchaseJwt));
            }
        } catch (JWTException $e) {
            if (!$retried) {
                return $this->decodePurchases($encodedPurchases, true);
            }
            // ignore if already retried
        } catch (AppException $e) {
            // ignore
        }

        return $decodedPurchases;
    }

    /**
     * @param array<string, array<int, DecodedPurchasesCollectionStruct>> $decodePurchases
     *
     * @return array<string, list<string>>
     */
    private function filterActive(array $decodePurchases): array
    {
        $activePurchases = [];

        foreach ($decodePurchases as $extensionName => $extensionPurchases) {
            foreach ($extensionPurchases as $purchases) {
                foreach ($purchases as $purchase) {
                    if (\is_string($purchase->nextBookingDate) && new \DateTime($purchase->nextBookingDate) < new \DateTime()) {
                        continue;
                    }

                    $activePurchases[$extensionName][] = $purchase->identifier;
                }
            }
        }

        return $activePurchases;
    }
}
