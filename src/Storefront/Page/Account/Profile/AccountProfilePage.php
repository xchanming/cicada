<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Account\Profile;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Salutation\SalutationCollection;
use Cicada\Storefront\Page\Page;

#[Package('checkout')]
class AccountProfilePage extends Page
{
    /**
     * @var SalutationCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $salutations;

    public function getSalutations(): SalutationCollection
    {
        return $this->salutations;
    }

    public function setSalutations(SalutationCollection $salutations): void
    {
        $this->salutations = $salutations;
    }
}
