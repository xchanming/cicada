<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfigSalesChannel;

use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigEntity;
use Cicada\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;

#[Package('checkout')]
class DocumentBaseConfigSalesChannelEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $documentBaseConfigId;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $salesChannelId;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $documentTypeId;

    /**
     * @var DocumentTypeEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $documentType;

    /**
     * @var DocumentBaseConfigEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $documentBaseConfig;

    /**
     * @var SalesChannelEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $salesChannel;

    public function getDocumentBaseConfigId(): string
    {
        return $this->documentBaseConfigId;
    }

    public function setDocumentBaseConfigId(string $documentBaseConfigId): void
    {
        $this->documentBaseConfigId = $documentBaseConfigId;
    }

    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(?string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }

    public function getDocumentTypeId(): string
    {
        return $this->documentTypeId;
    }

    public function setDocumentTypeId(string $documentTypeId): void
    {
        $this->documentTypeId = $documentTypeId;
    }

    public function getDocumentType(): ?DocumentTypeEntity
    {
        return $this->documentType;
    }

    public function setDocumentType(DocumentTypeEntity $documentType): void
    {
        $this->documentType = $documentType;
    }

    public function getDocumentBaseConfig(): ?DocumentBaseConfigEntity
    {
        return $this->documentBaseConfig;
    }

    public function setDocumentBaseConfig(DocumentBaseConfigEntity $documentBaseConfig): void
    {
        $this->documentBaseConfig = $documentBaseConfig;
    }

    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function setSalesChannel(?SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }
}
