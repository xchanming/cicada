<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Routing;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\Event\MaintenanceModeRequestEvent;
use Cicada\Core\Framework\Util\Json;
use Cicada\Core\SalesChannelRequest;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('core')]
class MaintenanceModeResolver
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public function shouldBeCached(Request $request): bool
    {
        return !$this->isActive($request) || !$this->isClientAllowed($request, self::getIps($request));
    }

    /**
     * @param array<string> $allowedIps
     */
    public function isClientAllowed(Request $request, array $allowedIps): bool
    {
        $isAllowed = IpUtils::checkIp((string) $request->getClientIp(), $allowedIps);

        $event = new MaintenanceModeRequestEvent($request, $allowedIps, $isAllowed);

        $this->eventDispatcher->dispatch($event);

        return $event->isClientAllowed();
    }

    public function isMaintenanceRequest(Request $request): bool
    {
        return $this->isActive($request) && !$this->isClientAllowed($request, self::getIps($request));
    }

    /**
     * @return string[]
     */
    private static function getIps(Request $request): array
    {
        $whitelist = $request->attributes->get(SalesChannelRequest::ATTRIBUTE_SALES_CHANNEL_MAINTENANCE_IP_WHITLELIST) ?? '';

        /** @var string[] $allowedIps */
        $allowedIps = Json::decodeToList((string) $whitelist);

        return $allowedIps;
    }

    private function isActive(Request $request): bool
    {
        return (bool) $request->attributes->get(SalesChannelRequest::ATTRIBUTE_SALES_CHANNEL_MAINTENANCE);
    }
}
