<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document;

use Cicada\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeEntity;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class DocumentEntity extends Entity
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $orderId;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $orderVersionId;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $documentTypeId;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $documentMediaFileId;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $fileType;

    /**
     * @var OrderEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $order;

    /**
     * @var array<string, mixed>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $config;

    /**
     * @var bool
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $sent;

    /**
     * @var bool
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $static;

    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $deepLinkCode;

    /**
     * @var DocumentTypeEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $documentType;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $referencedDocumentId;

    /**
     * @var DocumentEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $referencedDocument;

    /**
     * @var DocumentCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $dependentDocuments;

    /**
     * @var MediaEntity|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $documentMediaFile;

    protected ?string $documentNumber;

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): void
    {
        $this->fileType = $fileType;
    }

    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    public function setOrder(?OrderEntity $order): void
    {
        $this->order = $order;
    }

    public function getOrderVersionId(): string
    {
        return $this->orderVersionId;
    }

    public function setOrderVersionId(string $orderVersionId): void
    {
        $this->orderVersionId = $orderVersionId;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getSent(): bool
    {
        return $this->sent;
    }

    public function setSent(bool $sent): void
    {
        $this->sent = $sent;
    }

    public function getDeepLinkCode(): string
    {
        return $this->deepLinkCode;
    }

    public function setDeepLinkCode(string $deepLinkCode): void
    {
        $this->deepLinkCode = $deepLinkCode;
    }

    public function getDocumentType(): ?DocumentTypeEntity
    {
        return $this->documentType;
    }

    public function setDocumentType(DocumentTypeEntity $documentType): void
    {
        $this->documentType = $documentType;
    }

    public function getDocumentTypeId(): string
    {
        return $this->documentTypeId;
    }

    public function setDocumentTypeId(string $documentTypeId): void
    {
        $this->documentTypeId = $documentTypeId;
    }

    public function getReferencedDocumentId(): ?string
    {
        return $this->referencedDocumentId;
    }

    public function setReferencedDocumentId(?string $referencedDocumentId): void
    {
        $this->referencedDocumentId = $referencedDocumentId;
    }

    public function getReferencedDocument(): ?DocumentEntity
    {
        return $this->referencedDocument;
    }

    public function setReferencedDocument(?DocumentEntity $referencedDocument): void
    {
        $this->referencedDocument = $referencedDocument;
    }

    public function getDependentDocuments(): ?DocumentCollection
    {
        return $this->dependentDocuments;
    }

    public function setDependentDocuments(DocumentCollection $dependentDocuments): void
    {
        $this->dependentDocuments = $dependentDocuments;
    }

    public function isStatic(): bool
    {
        return $this->static;
    }

    public function setStatic(bool $static): void
    {
        $this->static = $static;
    }

    public function getDocumentMediaFile(): ?MediaEntity
    {
        return $this->documentMediaFile;
    }

    public function setDocumentMediaFile(?MediaEntity $documentMediaFile): void
    {
        $this->documentMediaFile = $documentMediaFile;
    }

    public function getDocumentMediaFileId(): ?string
    {
        return $this->documentMediaFileId;
    }

    public function setDocumentMediaFileId(?string $documentMediaFileId): void
    {
        $this->documentMediaFileId = $documentMediaFileId;
    }

    public function setDocumentNumber(?string $documentNumber): void
    {
        $this->documentNumber = $documentNumber;
    }

    public function getDocumentNumber(): ?string
    {
        return $this->documentNumber;
    }
}
