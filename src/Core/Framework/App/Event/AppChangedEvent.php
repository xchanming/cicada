<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Event;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Webhook\AclPrivilegeCollection;
use Cicada\Core\Framework\Webhook\Hookable;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
abstract class AppChangedEvent extends Event implements CicadaEvent, Hookable
{
    public function __construct(
        private readonly AppEntity $app,
        private readonly Context $context
    ) {
    }

    abstract public function getName(): string;

    public function getApp(): AppEntity
    {
        return $this->app;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getWebhookPayload(?AppEntity $app = null): array
    {
        return [];
    }

    public function isAllowed(string $appId, AclPrivilegeCollection $permissions): bool
    {
        return $appId === $this->app->getId();
    }
}
