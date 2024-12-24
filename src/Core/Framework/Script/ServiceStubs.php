<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Script;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Debugging\ScriptTraces;

/**
 * This class is intended for auto-completion in twig templates. So the developer can
 * set a doc block to get auto-completion for all services.
 *
 * @example: {# @var services \Cicada\Core\Framework\Script\ServiceStubs #}
 *
 * @method \Cicada\Core\Checkout\Cart\Facade\CartFacade cart()
 * @method \Cicada\Core\Checkout\Cart\Facade\PriceFactory price()
 * @method \Cicada\Core\Framework\DataAbstractionLayer\Facade\RepositoryFacade repository()
 * @method \Cicada\Core\System\SystemConfig\Facade\SystemConfigFacade config()
 * @method \Cicada\Core\Framework\DataAbstractionLayer\Facade\SalesChannelRepositoryFacade store()
 * @method \Cicada\Core\Framework\DataAbstractionLayer\Facade\RepositoryWriterFacade writer()
 * @method \Cicada\Core\Framework\Routing\Facade\RequestFacade request()
 * @method \Cicada\Core\Framework\Script\Api\ScriptResponseFactoryFacade response()
 * @method \Cicada\Core\Framework\Adapter\Cache\Script\Facade\CacheInvalidatorFacade cache()
 */
#[Package('core')]
final class ServiceStubs
{
    private string $hook;

    /**
     * @var array<string, array{deprecation?: string, service: object}>
     */
    private array $services = [];

    /**
     * @internal
     */
    public function __construct(string $hook)
    {
        $this->hook = $hook;
    }

    /**
     * @param array<mixed> $arguments
     *
     * @internal
     *
     * @param array<mixed> $arguments
     */
    public function __call(string $name, array $arguments): object
    {
        if (!isset($this->services[$name])) {
            throw ScriptException::serviceNotAvailableInHook($name, $this->hook);
        }

        if (isset($this->services[$name]['deprecation'])) {
            ScriptTraces::addDeprecationNotice($this->services[$name]['deprecation']);
        }

        return $this->services[$name]['service'];
    }

    /**
     * @internal
     */
    public function add(string $name, object $service, ?string $deprecationNotice = null): void
    {
        if (isset($this->services[$name])) {
            throw ScriptException::serviceAlreadyExists($name);
        }

        $this->services[$name]['service'] = $service;

        if ($deprecationNotice) {
            $this->services[$name]['deprecation'] = $deprecationNotice;
        }
    }

    /**
     * @internal
     */
    public function get(string $name): object
    {
        if (!isset($this->services[$name])) {
            throw ScriptException::serviceNotAvailableInHook($name, $this->hook);
        }

        if (isset($this->services[$name]['deprecation'])) {
            ScriptTraces::addDeprecationNotice($this->services[$name]['deprecation']);
        }

        return $this->services[$name]['service'];
    }
}
