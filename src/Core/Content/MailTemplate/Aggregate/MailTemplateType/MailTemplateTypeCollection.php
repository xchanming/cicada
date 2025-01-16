<?php declare(strict_types=1);

namespace Cicada\Core\Content\MailTemplate\Aggregate\MailTemplateType;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<MailTemplateTypeEntity>
 */
#[Package('after-sales')]
class MailTemplateTypeCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'mail_template_type_collection';
    }

    protected function getExpectedClass(): string
    {
        return MailTemplateTypeEntity::class;
    }
}
