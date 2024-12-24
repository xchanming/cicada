<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Changelog;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
enum ChangelogSection: string
{
    case core = 'Core';
    case api = 'API';
    case administration = 'Administration';
    case storefront = 'Storefront';
    case elasticsearch = 'Elasticsearch';
    case upgrade = 'Upgrade Information';
    case major = 'Next Major Version Changes';
}
