<?php declare(strict_types=1);

namespace Cicada\Core\Content\MailTemplate\Aggregate\MailTemplateTypeTranslation;

use Cicada\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeEntity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Cicada\Core\Framework\Log\Package;

#[Package('after-sales')]
class MailTemplateTypeTranslationEntity extends TranslationEntity
{
    use EntityCustomFieldsTrait;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $mailTemplateTypeId;

    /**
     * @var MailTemplateTypeEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $mailTemplateType;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $name;

    public function getMailTemplateTypeId(): string
    {
        return $this->mailTemplateTypeId;
    }

    public function setMailTemplateTypeId(string $mailTemplateTypeId): void
    {
        $this->mailTemplateTypeId = $mailTemplateTypeId;
    }

    public function getMailTemplateType(): ?MailTemplateTypeEntity
    {
        return $this->mailTemplateType;
    }

    public function setMailTemplateType(?MailTemplateTypeEntity $mailTemplateType): void
    {
        $this->mailTemplateType = $mailTemplateType;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
