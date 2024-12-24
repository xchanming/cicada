<?php declare(strict_types=1);

use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Controller\StorefrontController;
use Symfony\Component\Routing\Attribute\Route;

#[Package('core')]
#[Route(defaults: false)]
class InvalidAclRouteInRouteAttributeController extends StorefrontController
{
    #[Route(defaults: ['_httpCache' => true])]
    public function noAcl(): void
    {
    }

    #[Route(defaults: ['_acl' => 'string here'])]
    public function aclIsNotArray(): void
    {
    }

    #[Route(defaults: ['_acl' => [null]])]
    public function aclContainInvalidValues(): void
    {
    }

    public function noAttribute(): void
    {
    }
}
