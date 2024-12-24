<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Events;

use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\MailTemplate\MailTemplateEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\DataBag;

#[Package('services-settings')]
class FlowSendMailActionEvent implements CicadaEvent
{
    public function __construct(
        private readonly DataBag $dataBag,
        private readonly MailTemplateEntity $mailTemplate,
        private readonly StorableFlow $flow
    ) {
    }

    public function getContext(): Context
    {
        return $this->flow->getContext();
    }

    public function getDataBag(): DataBag
    {
        return $this->dataBag;
    }

    public function getMailTemplate(): MailTemplateEntity
    {
        return $this->mailTemplate;
    }

    public function getStorableFlow(): StorableFlow
    {
        return $this->flow;
    }
}
