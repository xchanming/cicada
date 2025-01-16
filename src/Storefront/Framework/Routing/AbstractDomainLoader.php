<?php declare(strict_types=1);

namespace Cicada\Storefront\Framework\Routing;

use Cicada\Core\Framework\Log\Package;

/**
 * @phpstan-type Domain = array{url: string, id: string, salesChannelId: string, typeId: string, snippetSetId: string, currencyId: string, languageId: string, themeId: string, maintenance: string, maintenanceIpWhitelist: string, locale: string, themeName: string, parentThemeName: string}
 */
#[Package('framework')]
abstract class AbstractDomainLoader
{
    abstract public function getDecorated(): AbstractDomainLoader;

    /**
     * @return array<string, Domain>
     */
    abstract public function load(): array;
}
