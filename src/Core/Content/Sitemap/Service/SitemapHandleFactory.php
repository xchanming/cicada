<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Service;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Package('services-settings')]
class SitemapHandleFactory implements SitemapHandleFactoryInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    /**
     * @deprecated tag:v6.7.0 - reason:new-optional-parameter - Parameter ?string $domainId = null will be added
     */
    public function create(
        FilesystemOperator $filesystem,
        SalesChannelContext $context,
        ?string $domain = null,
        /* , ?string $domainId = null */
    ): SitemapHandleInterface {
        $domainId = \func_num_args() > 3 ? func_get_arg(3) : null;

        return new SitemapHandle($filesystem, $context, $this->eventDispatcher, $domain, $domainId);
    }
}
