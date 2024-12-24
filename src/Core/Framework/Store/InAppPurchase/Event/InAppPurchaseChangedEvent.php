<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\InAppPurchase\Event;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Webhook\AclPrivilegeCollection;
use Cicada\Core\Framework\Webhook\Hookable;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @final
 */
#[Package('core')]
class InAppPurchaseChangedEvent extends Event implements Hookable
{
    final public const NAME = 'in_app_purchase.changed';

    public function __construct(
        protected string $extensionName,
        protected string $purchaseToken,
        protected ?string $appId,
        protected Context $context,
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPurchaseToken(): string
    {
        return $this->purchaseToken;
    }

    public function getExtensionName(): string
    {
        return $this->extensionName;
    }

    public function getAppId(): ?string
    {
        return $this->appId;
    }

    public function getWebhookPayload(?AppEntity $app = null): array
    {
        return [
            'purchaseToken' => $this->purchaseToken,
        ];
    }

    public function isAllowed(string $appId, AclPrivilegeCollection $permissions): bool
    {
        return $appId === $this->appId;
    }
}
