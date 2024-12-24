<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Manifest\Xml\CustomField;

use Cicada\Core\Framework\App\Manifest\Xml\XmlElement;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class CustomFields extends XmlElement
{
    /**
     * @var list<CustomFieldSet>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $customFieldSets = [];

    /**
     * @return list<CustomFieldSet>
     */
    public function getCustomFieldSets(): array
    {
        return $this->customFieldSets;
    }

    protected static function parse(\DOMElement $element): array
    {
        $customFieldSets = [];
        foreach ($element->getElementsByTagName('custom-field-set') as $customFieldSet) {
            $customFieldSets[] = CustomFieldSet::fromXml($customFieldSet);
        }

        return ['customFieldSets' => $customFieldSets];
    }
}
