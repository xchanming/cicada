<?php declare(strict_types=1);

namespace Cicada\Core\System\Currency;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Locale\LanguageLocaleCodeProvider;
use Symfony\Contracts\Service\ResetInterface;

#[Package('buyers-experience')]
class CurrencyFormatter implements ResetInterface
{
    private const REGEX_REMOVE_COUNTRY_CODE = '/^[A-Za-z]+/';

    /**
     * @var \NumberFormatter[]
     */
    private array $formatter = [];

    /**
     * @internal
     */
    public function __construct(private readonly LanguageLocaleCodeProvider $languageLocaleProvider)
    {
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function formatCurrencyByLanguage(float $price, string $currency, string $languageId, Context $context, ?int $decimals = null): string
    {
        $decimals ??= $context->getRounding()->getDecimals();

        $formatter = $this->getFormatter(
            $this->languageLocaleProvider->getLocaleForLanguageId($languageId)
        );
        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $decimals);

        return (string) preg_replace(self::REGEX_REMOVE_COUNTRY_CODE, '', (string) $formatter->formatCurrency($price, $currency));
    }

    public function reset(): void
    {
        $this->formatter = [];
    }

    private function getFormatter(string $locale): \NumberFormatter
    {
        if (isset($this->formatter[$locale])) {
            return $this->formatter[$locale];
        }

        return $this->formatter[$locale] = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
    }
}
