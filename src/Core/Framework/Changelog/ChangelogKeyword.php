<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Changelog;

use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
enum ChangelogKeyword: string
{
    case ADDED = 'Added';
    case REMOVED = 'Removed';
    case CHANGED = 'Changed';
    case DEPRECATED = 'Deprecated';
}
