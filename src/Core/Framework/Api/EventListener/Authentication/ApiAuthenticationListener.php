<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\EventListener\Authentication;

use Cicada\Core\Framework\Api\OAuth\BearerTokenValidator;
use Cicada\Core\Framework\Api\OAuth\SymfonyBearerTokenValidator;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\ApiContextRouteScopeDependant;
use Cicada\Core\Framework\Routing\KernelListenerPriorities;
use Cicada\Core\Framework\Routing\RouteScopeCheckTrait;
use Cicada\Core\Framework\Routing\RouteScopeRegistry;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
#[Package('framework')]
class ApiAuthenticationListener implements EventSubscriberInterface
{
    use RouteScopeCheckTrait;

    /**
     * @internal
     */
    public function __construct(
        private readonly BearerTokenValidator $bearerTokenValidator,
        private readonly SymfonyBearerTokenValidator $symfonyBearerTokenValidator,
        private readonly PsrHttpFactory $psrHttpFactory,
        private readonly AuthorizationServer $authorizationServer,
        private readonly UserRepositoryInterface $userRepository,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly RouteScopeRegistry $routeScopeRegistry,
        private readonly string $accessTokenTtl = 'PT10M',
        private readonly string $refreshTokenTtl = 'P1W'
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['setupOAuth', 128],
            ],
            KernelEvents::CONTROLLER => [
                ['validateRequest', KernelListenerPriorities::KERNEL_CONTROLLER_EVENT_PRIORITY_AUTH_VALIDATE],
            ],
        ];
    }

    public function setupOAuth(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $accessTokenInterval = new \DateInterval($this->accessTokenTtl);
        $refreshTokenInterval = new \DateInterval($this->refreshTokenTtl);

        $passwordGrant = new PasswordGrant($this->userRepository, $this->refreshTokenRepository);
        $passwordGrant->setRefreshTokenTTL($refreshTokenInterval);

        $refreshTokenGrant = new RefreshTokenGrant($this->refreshTokenRepository);
        $refreshTokenGrant->setRefreshTokenTTL($refreshTokenInterval);

        $this->authorizationServer->enableGrantType($passwordGrant, $accessTokenInterval);
        $this->authorizationServer->enableGrantType($refreshTokenGrant, $accessTokenInterval);
        $this->authorizationServer->enableGrantType(new ClientCredentialsGrant(), $accessTokenInterval);
    }

    public function validateRequest(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->attributes->get('auth_required', true)) {
            return;
        }

        if (!$this->isRequestScoped($request, ApiContextRouteScopeDependant::class)) {
            return;
        }

        if (Feature::isActive('v6.7.0.0')) {
            $this->symfonyBearerTokenValidator->validateAuthorization($event->getRequest());

            return;
        }

        $psr7Request = $this->psrHttpFactory->createRequest($event->getRequest());

        $psr7Request = $this->bearerTokenValidator->validateAuthorization($psr7Request);

        $request->attributes->add($psr7Request->getAttributes());
    }

    protected function getScopeRegistry(): RouteScopeRegistry
    {
        return $this->routeScopeRegistry;
    }
}
