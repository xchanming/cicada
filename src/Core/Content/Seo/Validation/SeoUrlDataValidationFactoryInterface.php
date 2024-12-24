<?php declare(strict_types=1);

namespace Cicada\Core\Content\Seo\Validation;

use Cicada\Core\Content\Seo\SeoUrlRoute\SeoUrlRouteConfig;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataValidationDefinition;

#[Package('buyers-experience')]
interface SeoUrlDataValidationFactoryInterface
{
    public function buildValidation(Context $context, SeoUrlRouteConfig $config): DataValidationDefinition;
}
