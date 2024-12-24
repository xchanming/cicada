<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules;

use Cicada\Core\DevOps\StaticAnalyze\PHPStan\Configuration;
use Cicada\Core\Framework\Adapter\Cache\ReverseProxy\FastlyReverseProxyGateway;
use Cicada\Core\Framework\Adapter\Cache\ReverseProxy\RedisReverseProxyGateway;
use Cicada\Core\Framework\Adapter\Cache\ReverseProxy\ReverseProxyException;
use Cicada\Core\Framework\Adapter\Cache\ReverseProxy\VarnishReverseProxyGateway;
use Cicada\Core\Framework\Framework;
use Cicada\Core\Framework\FrameworkException;
use Cicada\Core\Framework\HttpException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Kernel;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Throw_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @internal
 *
 * @implements Rule<Throw_>
 */
#[Package('core')]
class DomainExceptionRule implements Rule
{
    use InTestClassTrait;

    private const VALID_SUB_DOMAINS = [
        'Cart',
        'Payment',
        'Order',
    ];

    private const EXCLUDED_NAMESPACES = [
        'Cicada\Core\DevOps\StaticAnalyze\\',
    ];

    /**
     * @var array<string, string>
     */
    private const REMAPPED_DOMAINS = [
        Kernel::class => FrameworkException::class,
        Framework::class => FrameworkException::class,
        VarnishReverseProxyGateway::class => ReverseProxyException::class,
        FastlyReverseProxyGateway::class => ReverseProxyException::class,
        RedisReverseProxyGateway::class => ReverseProxyException::class,
    ];

    private const GLOBAL_EXCEPTIONS = [
        'Cicada\Core\Framework\FrameworkException::extensionResultNotSet',
    ];

    /**
     * @var array<string>
     */
    private array $validExceptionClasses;

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly Configuration $configuration,
    ) {
        // see src/Core/DevOps/StaticAnalyze/PHPStan/extension.neon for the default config
        $this->validExceptionClasses = $this->configuration->getAllowedNonDomainExceptions();
    }

    public function getNodeType(): string
    {
        return Throw_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->isInTestClass($scope) || !$scope->isInClass()) {
            return [];
        }

        if (!$node instanceof Throw_) {
            return [];
        }

        if ($node->expr instanceof StaticCall) {
            return $this->validateDomainExceptionClass($node->expr, $scope);
        }

        if (!$node->expr instanceof New_) {
            return [];
        }

        $namespace = $scope->getNamespace();
        if (\is_string($namespace)) {
            foreach (self::EXCLUDED_NAMESPACES as $excludedNamespace) {
                if (\str_starts_with($namespace, $excludedNamespace)) {
                    return [];
                }
            }
        }

        \assert($node->expr->class instanceof Name);
        $exceptionClass = $node->expr->class->toString();

        if (\in_array($exceptionClass, $this->validExceptionClasses, true)) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Throwing new exceptions within classes are not allowed. Please use domain exception pattern. See https://github.com/cicada-ag/platform/blob/v6.4.20.0/adr/2022-02-24-domain-exceptions.md')
                ->identifier('cicada.domainException')
                ->build(),
        ];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function validateDomainExceptionClass(StaticCall $node, Scope $scope): array
    {
        \assert($node->class instanceof Name);
        $exceptionClass = $node->class->toString();

        if (!\str_starts_with($exceptionClass, 'Cicada\\Core\\')) {
            return [];
        }

        $exception = $this->reflectionProvider->getClass($exceptionClass);
        if (!$exception->isSubclassOf(HttpException::class)) {
            return [
                RuleErrorBuilder::message(\sprintf('Domain exception class %s has to extend the \Cicada\Core\Framework\HttpException class', $exceptionClass))
                    ->identifier('cicada.domainException')
                    ->build(),
            ];
        }

        $reflection = $scope->getClassReflection();
        \assert($reflection !== null);
        if (!\str_starts_with($reflection->getName(), 'Cicada\\Core\\')) {
            return [];
        }

        if ($this->isRemapped($reflection->getName(), $exceptionClass)) {
            return [];
        }

        $parts = \explode('\\', $reflection->getName());

        $domain = $parts[2] ?? '';
        $sub = $parts[3] ?? '';

        $acceptedClasses = [
            \sprintf('Cicada\\Core\\%s\\%s\\%sException', $domain, $sub, $sub),
            \sprintf('Cicada\\Core\\%s\\%sException', $domain, $domain),
        ];

        foreach ($acceptedClasses as $expected) {
            if ($exceptionClass === $expected || $exception->isSubclassOf($expected)) {
                return [];
            }
        }

        // Is it in a subdomain?
        if (isset($parts[5]) && \in_array($parts[4], self::VALID_SUB_DOMAINS, true)) {
            $expectedSub = \sprintf('\\%s\\%sException', $parts[4], $parts[4]);
            if (\str_starts_with(strrev($exceptionClass), strrev($expectedSub))) {
                return [];
            }
        }

        if (method_exists($node->name, 'toString')) {
            $full = $exceptionClass . '::' . $node->name->toString();
            if (\in_array($full, self::GLOBAL_EXCEPTIONS, true)) {
                return [];
            }
        }

        return [
            RuleErrorBuilder::message(\sprintf('Expected domain exception class %s, got %s', $acceptedClasses[0], $exceptionClass))
                ->identifier('cicada.domainException')
                ->build(),
        ];
    }

    private function isRemapped(string $source, string $exceptionClass): bool
    {
        if (!\array_key_exists($source, self::REMAPPED_DOMAINS)) {
            return false;
        }

        return self::REMAPPED_DOMAINS[$source] === $exceptionClass;
    }
}
