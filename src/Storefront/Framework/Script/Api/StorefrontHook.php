<?php declare(strict_types=1);

namespace Cicada\Storefront\Framework\Script\Api;

use Cicada\Core\Framework\DataAbstractionLayer\Facade\RepositoryFacadeHookFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Facade\RepositoryWriterFacadeHookFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Facade\SalesChannelRepositoryFacadeHookFactory;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\Facade\RequestFacadeFactory;
use Cicada\Core\Framework\Script\Api\ScriptResponseFactoryFacadeHookFactory;
use Cicada\Core\Framework\Script\Execution\Awareness\SalesChannelContextAware;
use Cicada\Core\Framework\Script\Execution\Awareness\ScriptResponseAwareTrait;
use Cicada\Core\Framework\Script\Execution\Awareness\StoppableHook;
use Cicada\Core\Framework\Script\Execution\Awareness\StoppableHookTrait;
use Cicada\Core\Framework\Script\Execution\Hook;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\Facade\SystemConfigFacadeHookFactory;
use Cicada\Storefront\Page\Page;

/**
 * Triggered when the storefront endpoint /storefront/script/{hook} is called
 *
 * @hook-use-case custom_endpoint
 *
 * @since 6.4.9.0
 *
 * @final
 */
#[Package('framework')]
class StorefrontHook extends Hook implements SalesChannelContextAware, StoppableHook
{
    use ScriptResponseAwareTrait;
    use StoppableHookTrait;

    final public const HOOK_NAME = 'storefront-{hook}';

    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed> $query
     */
    public function __construct(
        private readonly string $script,
        private readonly array $request,
        private readonly array $query,
        private readonly Page $page,
        private readonly SalesChannelContext $salesChannelContext
    ) {
        parent::__construct($salesChannelContext->getContext());
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequest(): array
    {
        return $this->request;
    }

    /**
     * @return array<string, mixed>
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getName(): string
    {
        return \str_replace(
            ['{hook}'],
            [$this->script],
            self::HOOK_NAME
        );
    }

    public static function getServiceIds(): array
    {
        return [
            RepositoryFacadeHookFactory::class,
            SystemConfigFacadeHookFactory::class,
            SalesChannelRepositoryFacadeHookFactory::class,
            RepositoryWriterFacadeHookFactory::class,
            ScriptResponseFactoryFacadeHookFactory::class,
            RequestFacadeFactory::class,
        ];
    }

    public function getPage(): Page
    {
        return $this->page;
    }
}
