<?php declare(strict_types=1);

namespace Cicada\Administration\Framework\Routing;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\AbstractRouteScope;
use Cicada\Core\Framework\Routing\ApiContextRouteScopeDependant;
use Symfony\Component\HttpFoundation\Request;

#[Package('administration')]
class AdministrationRouteScope extends AbstractRouteScope implements ApiContextRouteScopeDependant
{
    final public const ID = 'administration';

    /**
     * @var string[]
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $allowedPaths;

    /**
     * @internal
     */
    public function __construct(string $administrationPathName = 'admin')
    {
        $this->allowedPaths = [$administrationPathName, 'api'];
    }

    public function isAllowed(Request $request): bool
    {
        return true;
    }

    public function getId(): string
    {
        return self::ID;
    }
}
