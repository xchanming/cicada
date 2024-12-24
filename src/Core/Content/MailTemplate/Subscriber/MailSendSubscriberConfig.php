<?php declare(strict_types=1);

namespace Cicada\Core\Content\MailTemplate\Subscriber;

use Cicada\Core\Content\Flow\Dispatching\Action\SendMailAction;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('buyers-experience')]
class MailSendSubscriberConfig extends Struct
{
    /**
     * @deprecated tag:v6.7.0 - Will be removed use `Cicada\Core\Content\Flow\Dispatching\Action\SendMailAction::ACTION_NAME` instead
     */
    final public const ACTION_NAME = SendMailAction::ACTION_NAME;

    /**
     * @deprecated tag:v6.7.0 - Will be removed use `Cicada\Core\Content\Flow\Dispatching\Action\SendMailAction::MAIL_CONFIG_EXTENSION` instead
     */
    final public const MAIL_CONFIG_EXTENSION = SendMailAction::MAIL_CONFIG_EXTENSION;

    /**
     * @var bool
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $skip;

    /**
     * @var array<string>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $documentIds = [];

    /**
     * @var array<string>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $mediaIds = [];

    /**
     * @param array<string> $documentIds
     * @param array<string> $mediaIds
     */
    public function __construct(
        bool $skip,
        array $documentIds = [],
        array $mediaIds = []
    ) {
        $this->skip = $skip;
        $this->documentIds = $documentIds;
        $this->mediaIds = $mediaIds;
    }

    public function skip(): bool
    {
        return $this->skip;
    }

    public function setSkip(bool $skip): void
    {
        $this->skip = $skip;
    }

    /**
     * @return array<string>
     */
    public function getDocumentIds(): array
    {
        return $this->documentIds;
    }

    /**
     * @return array<string>
     */
    public function getMediaIds(): array
    {
        return $this->mediaIds;
    }

    /**
     * @param array<string> $documentIds
     */
    public function setDocumentIds(array $documentIds): void
    {
        $this->documentIds = $documentIds;
    }

    /**
     * @param array<string> $mediaIds
     */
    public function setMediaIds(array $mediaIds): void
    {
        $this->mediaIds = $mediaIds;
    }
}
