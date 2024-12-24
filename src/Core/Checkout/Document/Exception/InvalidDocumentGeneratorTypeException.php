<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Exception;

use Cicada\Core\Checkout\Document\DocumentException;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class InvalidDocumentGeneratorTypeException extends DocumentException
{
}
