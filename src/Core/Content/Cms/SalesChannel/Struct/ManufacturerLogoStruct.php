<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\SalesChannel\Struct;

use Cicada\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
class ManufacturerLogoStruct extends ImageStruct
{
    /**
     * @var ProductManufacturerEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $manufacturer;

    public function getManufacturer(): ?ProductManufacturerEntity
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?ProductManufacturerEntity $manufacturer): void
    {
        $this->manufacturer = $manufacturer;
    }

    public function getApiAlias(): string
    {
        return 'cms_manufacturer_logo';
    }
}
