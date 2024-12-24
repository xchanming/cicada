<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules;

use Cicada\Core\Framework\Log\Package;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @implements Rule<InClassNode>
 *
 * @internal
 */
#[Package('core')]
class PackageAnnotationRule implements Rule
{
    /**
     * @internal
     */
    private const PRODUCT_AREA_MAPPING = [
        'inventory' => [
            '/Cicada\\\\Core\\\\Content\\\\(Product|ProductExport|Property)\\\\/',
            '/Cicada\\\\Core\\\\System\\\\(Currency|Unit)\\\\/',
            '/Cicada\\\\Storefront\\\\Page\\\\Product\\\\/',
        ],
        'content' => [
            '/Cicada\\\\Core\\\\Content\\\\(Media|Category|Cms|ContactForm|LandingPage)\\\\/',
            '/Cicada\\\\Storefront\\\\Page\\\\Cms\\\\/',
            '/Cicada\\\\Storefront\\\\Page\\\\LandingPage\\\\/',
            '/Cicada\\\\Storefront\\\\Page\\\\Contact\\\\/',
            '/Cicada\\\\Storefront\\\\Page\\\\Navigation\\\\/',
            '/Cicada\\\\Storefront\\\\Pagelet\\\\Menu\\\\/',
            '/Cicada\\\\Storefront\\\\Pagelet\\\\Footer\\\\/',
            '/Cicada\\\\Storefront\\\\Pagelet\\\\Header\\\\/',
        ],
        'services-settings' => [
            '/Cicada\\\\.*\\\\(Rule|Flow|ProductStream)\\\\/',
            '/Cicada\\\\Core\\\\Framework\\\\(Event)\\\\/',
            '/Cicada\\\\Core\\\\System\\\\(Tag)\\\\/',
            '/Cicada\\\\Core\\\\Content\\\\(ImportExport|Mail)\\\\/',
            '/Cicada\\\\Core\\\\Framework\\\\(Update)\\\\/',
            '/Cicada\\\\Core\\\\System\\\\(Country|CustomField|Integration|Language|Locale|Snippet|User)\\\\/',
            '/Cicada\\\\Storefront\\\\Pagelet\\\\Country\\\\/',
            '/Cicada\\\\Storefront\\\\Page\\\\Suggest\\\\/',
            '/Cicada\\\\Storefront\\\\Page\\\\Search\\\\/',
            '/Cicada\\\\Core\\\\Framework\\\\Store\\\\/',
        ],
        'sales-channel' => [
            '/Cicada\\\\Core\\\\Content\\\\(MailTemplate|Seo|Sitemap)\\\\/',
            '/Cicada\\\\Core\\\\System\\\\(SalesChannel)\\\\/',
            '/Cicada\\\\Storefront\\\\Page\\\\Sitemap\\\\/',
            '/Cicada\\\\Storefront\\\\Pagelet\\\\Captcha\\\\/',
        ],
        'checkout' => [
            '/Cicada\\\\Core\\\\Checkout\\\\(Cart|Payment|Promotion|Shipping)\\\\/',
            '/Cicada\\\\Core\\\\Checkout\\\\(Customer|Document|Order)\\\\/',
            '/Cicada\\\\Core\\\\Content\\\\(Newsletter)\\\\/',
            '/Cicada\\\\Core\\\\System\\\\(DeliveryTime|NumberRange|StateMachine)\\\\/',
            '/Cicada\\\\Core\\\\System\\\\(DeliveryTime|Salutation|Tax)\\\\/',
            '/Cicada\\\\Storefront\\\\Checkout\\\\/',
            '/Cicada\\\\Storefront\\\\Page\\\\Account\\\\/',
            '/Cicada\\\\Storefront\\\\Page\\\\Address\\\\/',
            '/Cicada\\\\Storefront\\\\Page\\\\Checkout\\\\/',
            '/Cicada\\\\Storefront\\\\Page\\\\Maintenance\\\\/',
            '/Cicada\\\\Storefront\\\\Page\\\\Newsletter\\\\/',
            '/Cicada\\\\Storefront\\\\Page\\\\Wishlist\\\\/',
            '/Cicada\\\\Storefront\\\\Pagelet\\\\Newsletter\\\\/',
            '/Cicada\\\\Storefront\\\\Pagelet\\\\Wishlist\\\\/',
        ],
        'storefront' => [
            '/Cicada\\\\Storefront\\\\Theme\\\\/',
            '/Cicada\\\\Storefront\\\\Controller\\\\/',
            '/Cicada\\\\Storefront\\\\(DependencyInjection|Migration|Event|Exception|Framework|Test)\\\\/',
        ],
        'core' => [
            '/Cicada\\\\Core\\\\Framework\\\\(Adapter|Api|App|Changelog|DataAbstractionLayer|Demodata|DependencyInjection)\\\\/',
            '/Cicada\\\\Core\\\\Framework\\\\(Increment|Log|MessageQueue|Migration|Parameter|Plugin|RateLimiter|Script|Routing|Struct|Util|Uuid|Validation|Webhook)\\\\/',
            '/Cicada\\\\Core\\\\DevOps\\\\/',
            '/Cicada\\\\Core\\\\Installer\\\\/',
            '/Cicada\\\\Core\\\\Maintenance\\\\/',
            '/Cicada\\\\Core\\\\Migration\\\\/',
            '/Cicada\\\\Core\\\\Profiling\\\\/',
            '/Cicada\\\\Elasticsearch\\\\/',
            '/Cicada\\\\Docs\\\\/',
            '/Cicada\\\\Core\\\\System\\\\(Annotation|CustomEntity|DependencyInjection|SystemConfig)\\\\/',
            '/Cicada\\\\.*\\\\(DataAbstractionLayer)\\\\/',
        ],
        'administration' => [
            '/Cicada\\\\Administration\\\\/',
        ],
        'data-services' => [
            '/Cicada\\\\Core\\\\System\\\\UsageData\\\\/',
        ],
    ];

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     *
     * @return array<array-key, RuleError|string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->getClassReflection()->isAnonymous()) {
            return [];
        }

        if ($this->isTestClass($node)) {
            return [];
        }

        $area = $this->getProductArea($node);

        if ($this->hasPackageAnnotation($node)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(\sprintf('This class is missing the "#[Package(...)]" attribute (recommendation: %s)', $area ?? 'unknown'))
                ->identifier('cicada.missingPackageAttribute')
                ->build(),
        ];
    }

    private function getProductArea(InClassNode $node): ?string
    {
        $namespace = $node->getClassReflection()->getName();

        foreach (self::PRODUCT_AREA_MAPPING as $area => $regexes) {
            foreach ($regexes as $regex) {
                if (preg_match($regex, $namespace)) {
                    return $area;
                }
            }
        }

        return null;
    }

    private function hasPackageAnnotation(InClassNode $class): bool
    {
        foreach ($class->getOriginalNode()->attrGroups as $group) {
            $name = $group->attrs[0]->name;

            if ($name->toString() === Package::class) {
                return true;
            }
        }

        return false;
    }

    private function isTestClass(InClassNode $node): bool
    {
        $namespace = $node->getClassReflection()->getName();

        if (\str_contains($namespace, '\\Tests\\') || \str_contains($namespace, '\\Test\\')) {
            return true;
        }

        $file = (string) $node->getClassReflection()->getFileName();
        if (\str_contains($file, '/tests/') || \str_contains($file, '/Tests/') || \str_contains($file, '/Test/')) {
            return true;
        }

        if ($node->getClassReflection()->getParentClass() === null) {
            return false;
        }

        return $node->getClassReflection()->getParentClass()->getName() === TestCase::class;
    }
}
