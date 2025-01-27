<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Header;

use Cicada\Core\Content\Category\CategoryCollection;
use Cicada\Core\Content\Category\Tree\Tree;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Currency\CurrencyCollection;
use Cicada\Core\System\Currency\CurrencyEntity;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\Language\LanguageEntity;
use Cicada\Storefront\Pagelet\NavigationPagelet;

#[Package('framework')]
class HeaderPagelet extends NavigationPagelet
{
    /**
     * @var LanguageCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $languages;

    /**
     * @var CurrencyCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $currencies;

    /**
     * @var LanguageEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $activeLanguage;

    /**
     * @var CurrencyEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $activeCurrency;

    /**
     * @var CategoryCollection
     *
     * @deprecated tag:v6.7.0 - Will be removed as it is unused
     */
    protected $serviceMenu;

    /**
     * @internal
     *
     * @deprecated tag:v6.7.0 - Parameter `serviceMenu` will be removed as it is unused
     */
    public function __construct(
        Tree $navigation,
        LanguageCollection $languages,
        CurrencyCollection $currencies,
        LanguageEntity $activeLanguage,
        CurrencyEntity $activeCurrency,
        CategoryCollection $serviceMenu
    ) {
        $this->languages = $languages;
        $this->currencies = $currencies;
        $this->activeLanguage = $activeLanguage;
        $this->activeCurrency = $activeCurrency;
        $this->serviceMenu = $serviceMenu;

        parent::__construct($navigation);
    }

    public function getLanguages(): LanguageCollection
    {
        return $this->languages;
    }

    public function getCurrencies(): CurrencyCollection
    {
        return $this->currencies;
    }

    public function getActiveLanguage(): LanguageEntity
    {
        return $this->activeLanguage;
    }

    public function getActiveCurrency(): CurrencyEntity
    {
        return $this->activeCurrency;
    }

    /**
     * @deprecated tag:v6.7.0 - Method will be removed as `serviceMenu` is unused
     */
    public function getServiceMenu(): CategoryCollection
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', Feature::deprecatedMethodMessage(__CLASS__, __METHOD__, 'v6.7.0.0'));

        return $this->serviceMenu;
    }
}
