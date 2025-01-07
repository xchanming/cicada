<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Service;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use League\Flysystem\FilesystemOperator;

#[Package('discovery')]
interface SitemapHandleFactoryInterface
{
    /**
     * @deprecated tag:v6.7.0 - reason:new-optional-parameter - Parameter ?string $domainId = null will be added
     */
    public function create(
        FilesystemOperator $filesystem,
        SalesChannelContext $context,
        ?string $domain = null,
        /* , ?string $domainId = null */
    ): SitemapHandleInterface;
}
