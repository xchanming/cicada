<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Maintenance;

use Cicada\Core\Content\Cms\CmsPageEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Page\Page;

#[Package('framework')]
class MaintenancePage extends Page
{
    /**
     * @var CmsPageEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $cmsPage;

    public function getCmsPage(): ?CmsPageEntity
    {
        return $this->cmsPage;
    }

    public function setCmsPage(CmsPageEntity $cmsPage): void
    {
        $this->cmsPage = $cmsPage;
    }
}
