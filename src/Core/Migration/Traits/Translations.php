<?php declare(strict_types=1);

namespace Cicada\Core\Migration\Traits;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class Translations
{
    /**
     * @var array<string, string|null>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $chinese;

    /**
     * @var array<string, string|null>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $english;

    /**
     * @param array<string, string|null> $chinese
     * @param array<string, string|null> $english
     */
    public function __construct(
        array $chinese,
        array $english
    ) {
        $this->chinese = $chinese;
        $this->english = $english;
    }

    /**
     * @return array<string, string|null>
     */
    public function getChinese(): array
    {
        return $this->chinese;
    }

    /**
     * @return array<string, string|null>
     */
    public function getEnglish(): array
    {
        return $this->english;
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return array_keys($this->english);
    }
}
