<?php declare(strict_types=1);

namespace Cicada\Core\Content\MailTemplate\Aggregate\MailHeaderFooter;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<MailHeaderFooterEntity>
 */
#[Package('buyers-experience')]
class MailHeaderFooterCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'mail_template_header_footer_collection';
    }

    protected function getExpectedClass(): string
    {
        return MailHeaderFooterEntity::class;
    }
}
