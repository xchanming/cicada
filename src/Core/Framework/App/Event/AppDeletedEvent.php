<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Event;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Webhook\AclPrivilegeCollection;
use Cicada\Core\Framework\Webhook\Hookable;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('core')]
class AppDeletedEvent extends Event implements CicadaEvent, Hookable
{
    final public const NAME = 'app.deleted';

    public function __construct(
        private readonly string $appId,
        private readonly Context $context,
        private readonly bool $keepUserData = false
    ) {
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function keepUserData(): bool
    {
        return $this->keepUserData;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getWebhookPayload(?AppEntity $app = null): array
    {
        return [
            'keepUserData' => $this->keepUserData,
        ];
    }

    public function isAllowed(string $appId, AclPrivilegeCollection $permissions): bool
    {
        return $appId === $this->getAppId();
    }
}
