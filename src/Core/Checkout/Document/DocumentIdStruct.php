<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('checkout')]
class DocumentIdStruct extends Struct
{
    public function __construct(
        protected string $id,
        protected string $deepLinkCode,
        protected ?string $mediaId = null
    ) {
    }

    public function getDeepLinkCode(): string
    {
        return $this->deepLinkCode;
    }

    public function setDeepLinkCode(string $deepLinkCode): void
    {
        $this->deepLinkCode = $deepLinkCode;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getMediaId(): ?string
    {
        return $this->mediaId;
    }

    public function getApiAlias(): string
    {
        return 'document_id';
    }
}
