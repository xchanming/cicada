<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Event;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
abstract class ManifestChangedEvent extends AppChangedEvent
{
    public const LIFECYCLE_EVENTS = [
        AppActivatedEvent::NAME,
        AppDeactivatedEvent::NAME,
        AppDeletedEvent::NAME,
        AppInstalledEvent::NAME,
        AppUpdatedEvent::NAME,
    ];

    public function __construct(
        AppEntity $app,
        private readonly Manifest $manifest,
        Context $context
    ) {
        parent::__construct($app, $context);
    }

    abstract public function getName(): string;

    public function getManifest(): Manifest
    {
        return $this->manifest;
    }

    public function getWebhookPayload(?AppEntity $app = null): array
    {
        return [
            'appVersion' => $this->manifest->getMetadata()->getVersion(),
        ];
    }
}
