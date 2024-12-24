<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel;

use Cicada\Core\Framework\DataAbstractionLayer\Dbal\ExceptionHandlerInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\Exception\LanguageOfSalesChannelDomainDeleteException;

#[Package('buyers-experience')]
class SalesChannelExceptionHandler implements ExceptionHandlerInterface
{
    public function getPriority(): int
    {
        return ExceptionHandlerInterface::PRIORITY_DEFAULT;
    }

    public function matchException(\Exception $e): ?\Exception
    {
        if (preg_match('/SQLSTATE\[23000\]:.*1451.*a foreign key constraint.*sales_channel_domain.*CONSTRAINT `fk.sales_channel_domain.language_id`/', $e->getMessage())) {
            return new LanguageOfSalesChannelDomainDeleteException($e);
        }

        if (preg_match('/SQLSTATE\[23000\]:.*1451.*a foreign key constraint.*product_export.*CONSTRAINT `fk.product_export.sales_channel_domain_id`/', $e->getMessage())) {
            return SalesChannelException::salesChannelDomainInUse($e);
        }

        return null;
    }
}
