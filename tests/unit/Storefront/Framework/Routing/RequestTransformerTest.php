<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Framework\Routing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Seo\AbstractSeoResolver;
use Cicada\Core\Framework\Routing\RequestTransformerInterface;
use Cicada\Storefront\Framework\Routing\AbstractDomainLoader;
use Cicada\Storefront\Framework\Routing\Exception\SalesChannelMappingException;
use Cicada\Storefront\Framework\Routing\RequestTransformer;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(RequestTransformer::class)]
class RequestTransformerTest extends TestCase
{
    /**
     * @param list<string> $registeredApiPrefixes
     */
    #[DataProvider('notRequiredSalesChannelProvider')]
    public function testSalesChannelIsNotRequired(array $registeredApiPrefixes, string $requestUri): void
    {
        $decorated = $this->createMock(RequestTransformerInterface::class);
        $decorated->method('transform')->willReturnCallback(fn ($request) => $request);

        $resolver = $this->createMock(AbstractSeoResolver::class);
        $domainLoader = $this->createMock(AbstractDomainLoader::class);

        // should not be called as the sales channel is not required
        $domainLoader->expects(static::never())->method('load');

        $requestTransformer = new RequestTransformer($decorated, $resolver, $registeredApiPrefixes, $domainLoader);

        $originalRequest = Request::create($requestUri);
        $transformedRequest = $requestTransformer->transform($originalRequest);

        static::assertEquals($originalRequest, $transformedRequest);
    }

    public function testSalesChannelIsRequired(): void
    {
        $decorated = $this->createMock(RequestTransformerInterface::class);
        $decorated->method('transform')->willReturnCallback(fn ($request) => $request);

        $resolver = $this->createMock(AbstractSeoResolver::class);
        $domainLoader = $this->createMock(AbstractDomainLoader::class);
        $domainLoader->expects(static::once())->method('load')->willReturn([]);

        // no registered api prefixes ==> sales channel is always required
        $registeredApiPrefixes = [];
        $requestTransformer = new RequestTransformer($decorated, $resolver, $registeredApiPrefixes, $domainLoader);

        $originalRequest = Request::create('http://cicada.com/api');

        static::expectException(SalesChannelMappingException::class);
        $requestTransformer->transform($originalRequest);
    }

    /**
     * @return array<string, array{registeredApiPrefixes: list<string>, requestUri: string}>
     */
    public static function notRequiredSalesChannelProvider(): iterable
    {
        yield 'Default case' => [
            'registeredApiPrefixes' => ['api'],
            'requestUri' => 'http://cicada.com/api',
        ];

        yield 'Case with trailing slash' => [
            'registeredApiPrefixes' => ['api'],
            'requestUri' => 'http://cicada.com/api/',
        ];

        yield 'Case with double leading slashes' => [
            'registeredApiPrefixes' => ['api'],
            'requestUri' => 'http://cicada.com//api',
        ];

        yield 'Case with double trailing slashes' => [
            'registeredApiPrefixes' => ['api'],
            'requestUri' => 'http://cicada.com/api//',
        ];

        yield 'Case with double leading and trailing slashes' => [
            'registeredApiPrefixes' => ['api'],
            'requestUri' => 'http://cicada.com//api//',
        ];

        // Allowedlist paths:
        yield '_wdt case' => [
            'registeredApiPrefixes' => ['api'],
            'requestUri' => 'http://cicada.com/_wdt/',
        ];

        yield '_profiler case' => [
            'registeredApiPrefixes' => ['api'],
            'requestUri' => 'http://cicada.com/_profiler/',
        ];

        yield '_error case' => [
            'registeredApiPrefixes' => ['api'],
            'requestUri' => 'http://cicada.com/_error/',
        ];

        yield 'payment finalize-transaction case' => [
            'registeredApiPrefixes' => ['api'],
            'requestUri' => 'http://cicada.com/payment/finalize-transaction/',
        ];

        yield 'installer case' => [
            'registeredApiPrefixes' => ['api'],
            'requestUri' => 'http://cicada.com/installer',
        ];

        yield '_fragment case' => [
            'registeredApiPrefixes' => ['api'],
            'requestUri' => 'http://cicada.com/_fragment/',
        ];
    }
}
