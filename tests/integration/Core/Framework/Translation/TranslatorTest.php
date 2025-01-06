<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Translation;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Adapter\Translation\Translator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\ReflectionHelper;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\SalesChannelRequest;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\Snippet\Files\SnippetFileCollection;
use Cicada\Core\System\Snippet\SnippetDefinition;
use Cicada\Core\Test\AppSystemTestBehaviour;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Theme\DatabaseSalesChannelThemeLoader;
use Cicada\Storefront\Theme\ThemeService;
use Cicada\Tests\Integration\Core\Framework\Translation\Fixtures\UnitTest_SnippetFile;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * @internal
 */
class TranslatorTest extends TestCase
{
    use AppSystemTestBehaviour;
    use IntegrationTestBehaviour;

    private Connection $connection;

    private Translator $translator;

    private EntityRepository $snippetRepository;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
        $this->translator = static::getContainer()->get(Translator::class);
        $this->snippetRepository = static::getContainer()->get('snippet.repository');

        $this->translator->reset();
        $this->translator->warmUp('');
    }

    public function testPassthru(): void
    {
        $snippetFile = new UnitTest_SnippetFile();
        static::getContainer()->get(SnippetFileCollection::class)->add($snippetFile);

        $stack = static::getContainer()->get(RequestStack::class);
        $prop = ReflectionHelper::getProperty(RequestStack::class, 'requests');
        $prop->setValue($stack, []);

        // fake request
        $request = new Request();
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_DOMAIN_SNIPPET_SET_ID, $this->getSnippetSetIdForLocale('en-GB'));
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_DOMAIN_LOCALE, 'en-GB');

        $stack->push($request);
        $result = $this->translator->getCatalogue('en-GB')->get('frontend.note.item.NoteLinkZoom');
        $prop->setValue($stack, []);

        static::assertEquals(
            'Enlarge',
            $result
        );
    }

    public function testSimpleOverwrite(): void
    {
        $context = Context::createDefaultContext();

        $snippet = [
            'translationKey' => 'new.unit.test.key',
            'value' => 'Realisiert mit Unit test',
            'setId' => $this->getSnippetSetIdForLocale('en-GB'),
            'author' => 'Cicada',
        ];
        $this->snippetRepository->create([$snippet], $context);

        // fake request
        $request = new Request();

        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_DOMAIN_SNIPPET_SET_ID, $this->getSnippetSetIdForLocale('en-GB'));
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_DOMAIN_LOCALE, 'en-GB');

        static::getContainer()->get(RequestStack::class)->push($request);

        // get overwritten string
        static::assertEquals(
            $snippet['value'],
            $this->translator->getCatalogue('en-GB')->get('new.unit.test.key')
        );
        static::assertSame(
            $request,
            static::getContainer()->get(RequestStack::class)->pop()
        );
    }

    public function testSymfonyDefaultTranslationFallback(): void
    {
        $this->translator->reset();
        $catalogue = $this->translator->getCatalogue('en');
        static::assertInstanceOf(MessageCatalogueInterface::class, $catalogue->getFallbackCatalogue());
        static::assertEquals('en_GB', $catalogue->getFallbackCatalogue()->getLocale());

        $this->translator->reset();
        $catalogue = $this->translator->getCatalogue('en_GB');
        static::assertInstanceOf(MessageCatalogueInterface::class, $catalogue->getFallbackCatalogue());
        static::assertEquals('en_001', $catalogue->getFallbackCatalogue()->getLocale());

        $this->translator->reset();
        $catalogue = $this->translator->getCatalogue('en-GB');
        $fallback = $catalogue->getFallbackCatalogue();
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback);
        static::assertEquals('en', $fallback->getLocale());
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback->getFallbackCatalogue());
        static::assertEquals('en_GB', $fallback->getFallbackCatalogue()->getLocale());

        $this->translator->reset();
        $catalogue = $this->translator->getCatalogue('de');
        $fallback = $catalogue->getFallbackCatalogue();
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback);
        static::assertEquals('en_GB', $fallback->getLocale());
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback->getFallbackCatalogue());
        static::assertEquals('en', $fallback->getFallbackCatalogue()->getLocale());

        $this->translator->reset();
        $catalogue = $this->translator->getCatalogue('de_DE');
        $fallback = $catalogue->getFallbackCatalogue();
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback);
        static::assertEquals('de', $fallback->getLocale());
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback->getFallbackCatalogue());
        static::assertEquals('en_GB', $fallback->getFallbackCatalogue()->getLocale());
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback->getFallbackCatalogue()->getFallbackCatalogue());
        static::assertEquals('en', $fallback->getFallbackCatalogue()->getFallbackCatalogue()->getLocale());

        $this->translator->reset();
        $catalogue = $this->translator->getCatalogue('zh-CN');
        $fallback = $catalogue->getFallbackCatalogue();
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback);
        static::assertEquals('en', $fallback->getLocale());
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback->getFallbackCatalogue());
        static::assertEquals('en_GB', $fallback->getFallbackCatalogue()->getLocale());
        $this->translator->reset();
    }

    public function testSymfonyDefaultTranslationFallbackWithCustomCicadaDefaultLanguage(): void
    {
        $this->switchDefaultLanguage();

        $catalogue = $this->translator->getCatalogue('en');
        static::assertInstanceOf(MessageCatalogueInterface::class, $catalogue->getFallbackCatalogue());
        static::assertEquals('en_GB', $catalogue->getFallbackCatalogue()->getLocale());

        $this->translator->reset();
        $catalogue = $this->translator->getCatalogue('en_GB');
        static::assertInstanceOf(MessageCatalogueInterface::class, $catalogue->getFallbackCatalogue());
        static::assertEquals('en_001', $catalogue->getFallbackCatalogue()->getLocale());

        $this->translator->reset();
        $catalogue = $this->translator->getCatalogue('en-GB');
        $fallback = $catalogue->getFallbackCatalogue();
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback);
        static::assertEquals('zh', $fallback->getLocale());
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback->getFallbackCatalogue());
        static::assertEquals('en_GB', $fallback->getFallbackCatalogue()->getLocale());
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback->getFallbackCatalogue()->getFallbackCatalogue());
        static::assertEquals('en', $fallback->getFallbackCatalogue()->getFallbackCatalogue()->getLocale());

        $this->translator->reset();
        $catalogue = $this->translator->getCatalogue('de');
        $fallback = $catalogue->getFallbackCatalogue();
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback);
        static::assertEquals('en_GB', $fallback->getLocale());
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback->getFallbackCatalogue());
        static::assertEquals('en', $fallback->getFallbackCatalogue()->getLocale());

        $this->translator->reset();
        $catalogue = $this->translator->getCatalogue('de_DE');
        $fallback = $catalogue->getFallbackCatalogue();
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback);
        static::assertEquals('de', $fallback->getLocale());
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback->getFallbackCatalogue());
        static::assertEquals('en_GB', $fallback->getFallbackCatalogue()->getLocale());
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback->getFallbackCatalogue()->getFallbackCatalogue());
        static::assertEquals('en', $fallback->getFallbackCatalogue()->getFallbackCatalogue()->getLocale());

        $this->translator->reset();
        $catalogue = $this->translator->getCatalogue('zh-CN');
        $fallback = $catalogue->getFallbackCatalogue();
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback);
        static::assertEquals('zh', $fallback->getLocale());
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback->getFallbackCatalogue());
        static::assertEquals('en_GB', $fallback->getFallbackCatalogue()->getLocale());
        static::assertInstanceOf(MessageCatalogueInterface::class, $fallback->getFallbackCatalogue()->getFallbackCatalogue());
        static::assertEquals('en', $fallback->getFallbackCatalogue()->getFallbackCatalogue()->getLocale());
        $this->translator->reset();
    }

    public function testTranslatorCustomLocaleAndFallback(): void
    {
        $context = Context::createDefaultContext();

        $snippets = [
            [
                'translationKey' => 'new.unit.test.key',
                'value' => 'Realized with Unit test',
                'setId' => $this->getSnippetSetIdForLocale('en-GB'),
                'author' => 'Cicada',
            ],
            [
                'translationKey' => 'new.unit.test.key',
                'value' => 'Realisiert mit Unit test',
                'setId' => $this->getSnippetSetIdForLocale('zh-CN'),
                'author' => 'Cicada',
            ],
        ];
        $this->snippetRepository->create($snippets, $context);

        // fake request
        $request = new Request();

        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_DOMAIN_SNIPPET_SET_ID, $this->getSnippetSetIdForLocale('en-GB'));
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_DOMAIN_LOCALE, 'en-GB');

        static::getContainer()->get(RequestStack::class)->push($request);

        // get overwritten string
        static::assertEquals(
            $snippets[0]['value'],
            $this->translator->trans('new.unit.test.key', [], null, 'en-GB')
        );
        static::assertEquals(
            $snippets[1]['value'],
            $this->translator->trans('new.unit.test.key', [], null, 'zh-CN')
        );
        static::assertEquals(
            $snippets[0]['value'],
            $this->translator->trans('new.unit.test.key', [], null, 'en')
        );
        static::assertEquals(
            $snippets[1]['value'],
            $this->translator->trans('new.unit.test.key', [], null, 'zh-CN')
        );
        static::assertEquals(
            $snippets[0]['value'],
            $this->translator->trans('new.unit.test.key')
        );

        $this->translator->setLocale('zh-CN');
        static::assertEquals(
            $snippets[1]['value'],
            $this->translator->trans('new.unit.test.key')
        );

        static::assertSame(
            $request,
            static::getContainer()->get(RequestStack::class)->pop()
        );
    }

    public function testDeleteSnippet(): void
    {
        $snippetRepository = static::getContainer()->get('snippet.repository');
        $snippet = [
            'id' => Uuid::randomHex(),
            'translationKey' => 'foo',
            'value' => 'bar',
            'setId' => $this->getSnippetSetIdForLocale('en-GB'),
            'author' => 'Cicada',
        ];

        $created = $snippetRepository->create([$snippet], Context::createDefaultContext())->getEventByEntityName(SnippetDefinition::ENTITY_NAME);
        static::assertInstanceOf(EntityWrittenEvent::class, $created);
        static::assertEquals([$snippet['id']], $created->getIds());

        $deleted = $snippetRepository->delete([['id' => $snippet['id']]], Context::createDefaultContext())->getEventByEntityName(SnippetDefinition::ENTITY_NAME);
        static::assertInstanceOf(EntityWrittenEvent::class, $deleted);
        static::assertEquals([$snippet['id']], $deleted->getIds());
    }

    public function testItReplacesReservedCharacter(): void
    {
        static::assertEquals('translator.<_r_strong>', Translator::buildName('</strong>'));
    }

    public function testThemeSnippetsGetsMergedWithOverride(): void
    {
        if (!static::getContainer()->has(ThemeService::class) || !static::getContainer()->has('theme.repository')) {
            static::markTestSkipped('This test needs storefront to be installed.');
        }

        $salesChannelContext = static::getContainer()->get(SalesChannelContextFactory::class)->create(
            Uuid::randomHex(),
            TestDefaults::SALES_CHANNEL
        );

        $translator = static::getContainer()->get(Translator::class);
        $themeService = static::getContainer()->get(ThemeService::class);
        $themeRepo = static::getContainer()->get('theme.repository');
        $loader = static::getContainer()->get(DatabaseSalesChannelThemeLoader::class);

        // Install the app
        $this->loadAppsFromDir(__DIR__ . '/Fixtures/theme');
        $this->reloadAppSnippets();

        // Ensure the default Storefront theme is active
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', 'Storefront'));
        $defaultThemeId = $themeRepo->searchIds($criteria, $salesChannelContext->getContext())->firstId();
        static::assertNotNull($defaultThemeId, 'Default theme not found');
        $themeService->assignTheme($defaultThemeId, $salesChannelContext->getSalesChannelId(), $salesChannelContext->getContext(), true);

        // Inject the sales channel and assert that the original snippet is used
        $loader->reset();

        // Assign the SwagTheme and assert that the snippet is overwritten
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', 'SwagTheme'));
        $themeId = $themeRepo->searchIds($criteria, $salesChannelContext->getContext())->firstId();

        static::assertNotNull($themeId);

        $themeService->assignTheme($themeId, $salesChannelContext->getSalesChannelId(), $salesChannelContext->getContext(), true);

        $translator->reset();
        $loader->reset();

        // Assign the Storefront theme again and assert that the original snippet is used again
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', 'Storefront'));
        $themeId = $themeRepo->searchIds($criteria, $salesChannelContext->getContext())->firstId();
        static::assertNotNull($themeId);
    }

    #[DataProvider('pluralTranslationProvider')]
    public function testPluralRules(string $expected, string $id, int $number, string $locale): void
    {
        static::assertEquals($expected, $this->translator->trans($id, ['%count%' => (string) $number], null, $locale));
    }

    /**
     * @return list<array{string, string, int, string}>
     */
    public static function pluralTranslationProvider(): array
    {
        return [
            // Test English plural rules
            ['There are 0 apples', 'There is one apple|There are %count% apples', 0, 'en-GB'],
            ['There is one apple', 'There is one apple|There are %count% apples', 1, 'en-GB'],
            ['There are 2 apples', 'There is one apple|There are %count% apples', 2, 'en-GB'],
            ['There are 21 apples', 'There is one apple|There are %count% apples', 21, 'en-GB'],

            ['There are 0 apples', 'There is one apple|There are %count% apples', 0, 'en_GB'],
            ['There is one apple', 'There is one apple|There are %count% apples', 1, 'en_GB'],
            ['There are 2 apples', 'There is one apple|There are %count% apples', 2, 'en_GB'],
            ['There are 21 apples', 'There is one apple|There are %count% apples', 21, 'en_GB'],

            // Test Ukrainian plural rules
            ['0 яблук', '%count% яблуко|%count% яблука|%count% яблук', 0, 'uk-UA'],
            ['1 яблуко', '%count% яблуко|%count% яблука|%count% яблук', 1, 'uk-UA'],
            ['2 яблука', '%count% яблуко|%count% яблука|%count% яблук', 2, 'uk-UA'],
            ['5 яблук', '%count% яблуко|%count% яблука|%count% яблук', 5, 'uk-UA'],
            ['21 яблуко', '%count% яблуко|%count% яблука|%count% яблук', 21, 'uk-UA'],

            ['0 яблук', '%count% яблуко|%count% яблука|%count% яблук', 0, 'uk_UA'],
            ['1 яблуко', '%count% яблуко|%count% яблука|%count% яблук', 1, 'uk_UA'],
            ['2 яблука', '%count% яблуко|%count% яблука|%count% яблук', 2, 'uk_UA'],
            ['5 яблук', '%count% яблуко|%count% яблука|%count% яблук', 5, 'uk_UA'],
            ['21 яблуко', '%count% яблуко|%count% яблука|%count% яблук', 21, 'uk_UA'],
        ];
    }

    private function switchDefaultLanguage(): void
    {
        $currentDeId = $this->connection->fetchOne(
            'SELECT language.id
             FROM language
             INNER JOIN locale ON translation_code_id = locale.id
             WHERE locale.code = "zh-CN"'
        );

        $stmt = $this->connection->prepare(
            'UPDATE language
             SET id = :newId
             WHERE id = :oldId'
        );

        // assign new uuid to old DEFAULT
        $stmt->executeStatement([
            'newId' => Uuid::randomBytes(),
            'oldId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
        ]);

        // change id to DEFAULT
        $stmt->executeStatement([
            'newId' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
            'oldId' => $currentDeId,
        ]);
    }
}
