<?php declare(strict_types=1);

namespace Cicada\Core\Content\Breadcrumb\Struct;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

/**
 * @experimental stableVersion:v6.7.0 feature:BREADCRUMB_STORE_API
 */
#[Package('inventory')]
class BreadcrumbCollection extends Struct
{
    /**
     * @param array<int, Breadcrumb> $breadcrumbs
     */
    public function __construct(
        public array $breadcrumbs
    ) {
    }

    public function getBreadcrumb(int $index): ?Breadcrumb
    {
        return $this->breadcrumbs[$index] ?? null;
    }

    /**
     * @return array<int, Breadcrumb>
     */
    public function getBreadcrumbs(): array
    {
        return $this->breadcrumbs;
    }

    public function getApiAlias(): string
    {
        return 'breadcrumb_collection';
    }
}
