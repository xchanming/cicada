<?php declare(strict_types=1);

namespace Cicada\Core\Content\Mail\Service;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Mime\Email;

#[Package('services-settings')]
abstract class AbstractMailService
{
    abstract public function getDecorated(): AbstractMailService;

    abstract public function send(array $data, Context $context, array $templateData = []): ?Email;
}
