<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\SystemConfig;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Exception\InvalidUuidException;
use Cicada\Core\System\SystemConfig\Event\SystemConfigChangedHook;
use Cicada\Core\System\SystemConfig\Exception\InvalidDomainException;
use Cicada\Core\System\SystemConfig\Exception\InvalidKeyException;
use Cicada\Core\System\SystemConfig\Exception\InvalidSettingValueException;
use Cicada\Core\System\SystemConfig\SymfonySystemConfigService;
use Cicada\Core\System\SystemConfig\SystemConfigLoader;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\System\SystemConfig\Util\ConfigReader;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
class SystemConfigServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private SystemConfigService $systemConfigService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->systemConfigService = new SystemConfigService(
            static::getContainer()->get(Connection::class),
            static::getContainer()->get(ConfigReader::class),
            static::getContainer()->get(SystemConfigLoader::class),
            static::getContainer()->get('event_dispatcher'),
            new SymfonySystemConfigService([]),
            false
        );
    }

    /**
     * @return array<mixed>
     */
    public static function differentTypesProvider(): array
    {
        return [
            [true],
            [false],
            [null],
            [0],
            [1234],
            [1243.42314],
            [''],
            ['test'],
            [['foo' => 'bar']],
        ];
    }

    /**
     * @param array<mixed>|bool|int|float|string|null $expected
     */
    #[DataProvider('differentTypesProvider')]
    public function testSetGetDifferentTypes($expected): void
    {
        $this->systemConfigService->set('foo.bar', $expected);
        $actual = $this->systemConfigService->get('foo.bar');
        static::assertSame($expected, $actual);
    }

    /**
     * @return array<mixed>
     */
    public static function getStringProvider(): array
    {
        return [
            [true, '1'],
            [false, ''],
            [null, ''],
            [0, '0'],
            [1234, '1234'],
            [1243.42314, '1243.42314'],
            ['', ''],
            ['test', 'test'],
            [['foo' => 'bar'], ''],
        ];
    }

    /**
     * @param array<mixed>|bool|int|float|string|null $writtenValue
     */
    #[DataProvider('getStringProvider')]
    public function testGetString($writtenValue, string $expected): void
    {
        $this->systemConfigService->set('foo.bar', $writtenValue);
        if (\is_array($writtenValue)) {
            $this->expectException(InvalidSettingValueException::class);
            $this->expectExceptionMessage('Invalid value for \'foo.bar\'. Must be of type \'string\'. But is of type \'array\'');
        }
        $actual = $this->systemConfigService->getString('foo.bar');
        static::assertSame($expected, $actual);
    }

    /**
     * @return array<mixed>
     */
    public static function getIntProvider(): array
    {
        return [
            [true, 1],
            [false, 0],
            [null, 0],
            [0, 0],
            [1234, 1234],
            [1243.42314, 1243],
            ['', 0],
            ['test', 0],
            [['foo' => 'bar'], 0],
        ];
    }

    /**
     * @param array<mixed>|bool|int|float|string|null $writtenValue
     */
    #[DataProvider('getIntProvider')]
    public function testGetInt($writtenValue, int $expected): void
    {
        $this->systemConfigService->set('foo.bar', $writtenValue);
        if (\is_array($writtenValue)) {
            $this->expectException(InvalidSettingValueException::class);
            $this->expectExceptionMessage('Invalid value for \'foo.bar\'. Must be of type \'int\'. But is of type \'array\'');
        }
        $actual = $this->systemConfigService->getInt('foo.bar');
        static::assertSame($expected, $actual);
    }

    /**
     * @return array<mixed>
     */
    public static function getFloatProvider(): array
    {
        return [
            [true, 1],
            [false, 0],
            [null, 0],
            [0, 0],
            [1234, 1234],
            [1243.42314, 1243.42314],
            ['', 0],
            ['test', 0],
            [['foo' => 'bar'], 0],
        ];
    }

    /**
     * @param array<mixed>|bool|int|float|string|null $writtenValue
     */
    #[DataProvider('getFloatProvider')]
    public function testGetFloat($writtenValue, float $expected): void
    {
        $this->systemConfigService->set('foo.bar', $writtenValue);
        if (\is_array($writtenValue)) {
            $this->expectException(InvalidSettingValueException::class);
            $this->expectExceptionMessage('Invalid value for \'foo.bar\'. Must be of type \'float\'. But is of type \'array\'');
        }
        $actual = $this->systemConfigService->getFloat('foo.bar');
        static::assertSame($expected, $actual);
    }

    /**
     * @return array<mixed>
     */
    public static function getBoolProvider(): array
    {
        return [
            [true, true],
            [false, false],
            [null, false],
            [0, false],
            [1234, true],
            [1243.42314, true],
            ['', false],
            ['test', true],
            [['foo' => 'bar'], true],
            [[], false],
        ];
    }

    /**
     * @param array<mixed>|bool|int|float|string|null $writtenValue
     */
    #[DataProvider('getBoolProvider')]
    public function testGetBool($writtenValue, bool $expected): void
    {
        $this->systemConfigService->set('foo.bar', $writtenValue);
        $actual = $this->systemConfigService->getBool('foo.bar');
        static::assertSame($expected, $actual);
    }

    /**
     * mysql 5.7.30 casts 0.0 to 0
     */
    public function testFloatZero(): void
    {
        $this->systemConfigService->set('foo.bar', 0.0);
        $actual = $this->systemConfigService->get('foo.bar');
        static::assertEquals(0.0, $actual);
    }

    public function testSetGetSalesChannel(): void
    {
        $this->systemConfigService->set('foo.bar', 'test');
        $actual = $this->systemConfigService->get('foo.bar', TestDefaults::SALES_CHANNEL);
        static::assertEquals('test', $actual);

        $this->systemConfigService->set('foo.bar', 'override', TestDefaults::SALES_CHANNEL);
        $actual = $this->systemConfigService->get('foo.bar', TestDefaults::SALES_CHANNEL);
        static::assertEquals('override', $actual);

        $this->systemConfigService->set('foo.bar', '', TestDefaults::SALES_CHANNEL);
        $actual = $this->systemConfigService->get('foo.bar', TestDefaults::SALES_CHANNEL);
        static::assertEquals('', $actual);
    }

    public function testSetGetSalesChannelBool(): void
    {
        $this->systemConfigService->set('foo.bar', false);
        $actual = $this->systemConfigService->get('foo.bar', TestDefaults::SALES_CHANNEL);
        static::assertFalse($actual);

        $this->systemConfigService->set('foo.bar', true, TestDefaults::SALES_CHANNEL);
        $actual = $this->systemConfigService->get('foo.bar', TestDefaults::SALES_CHANNEL);
        static::assertTrue($actual);
    }

    public function testGetDomainNoData(): void
    {
        $actual = $this->systemConfigService->getDomain('foo');
        static::assertEquals([], $actual);

        $actual = $this->systemConfigService->getDomain('foo', null, true);
        static::assertEquals([], $actual);

        $actual = $this->systemConfigService->getDomain('foo', TestDefaults::SALES_CHANNEL);
        static::assertEquals([], $actual);

        $actual = $this->systemConfigService->getDomain('foo', TestDefaults::SALES_CHANNEL, true);
        static::assertEquals([], $actual);
    }

    public function testGetDomain(): void
    {
        $this->systemConfigService->set('foo.a', 'a');
        $this->systemConfigService->set('foo.b', 'b');
        $this->systemConfigService->set('foo.c', 'c');
        $this->systemConfigService->set('foo.c', 'c override', TestDefaults::SALES_CHANNEL);

        $expected = [
            'foo.a' => 'a',
            'foo.b' => 'b',
            'foo.c' => 'c',
        ];
        $actual = $this->systemConfigService->getDomain('foo');
        static::assertEquals($expected, $actual);

        $expected = [
            'foo.a' => 'a',
            'foo.b' => 'b',
            'foo.c' => 'c override',
        ];
        $actual = $this->systemConfigService->getDomain('foo', TestDefaults::SALES_CHANNEL, true);
        static::assertEquals($expected, $actual);

        $expected = [
            'foo.c' => 'c override',
        ];
        $actual = $this->systemConfigService->getDomain('foo', TestDefaults::SALES_CHANNEL);
        static::assertEquals($expected, $actual);
    }

    public function testGetDomainInherit(): void
    {
        $this->systemConfigService->set('foo.bar', 'test');
        $this->systemConfigService->set('foo.bar', 'override', TestDefaults::SALES_CHANNEL);
        $this->systemConfigService->set('foo.bar', '', TestDefaults::SALES_CHANNEL);

        $expected = ['foo.bar' => 'test'];
        $actual = $this->systemConfigService->getDomain('foo', TestDefaults::SALES_CHANNEL, true);

        static::assertEquals($expected, $actual);
    }

    public function testGetDomainInheritWithBooleanValue(): void
    {
        $this->systemConfigService->set('foo.bar', true);
        $actual = $this->systemConfigService->getDomain('foo', TestDefaults::SALES_CHANNEL, true);

        // assert that the service reads the default value, when no sales-channel-specific value is configured
        static::assertSame(['foo.bar' => true], $actual);

        $this->systemConfigService->set('foo.bar', false, TestDefaults::SALES_CHANNEL);
        $actual = $this->systemConfigService->getDomain('foo', TestDefaults::SALES_CHANNEL, true);

        // assert that the service reads the sales-channel-specific value when one is configured
        static::assertSame(['foo.bar' => false], $actual);
    }

    public function testGetDomainWithDots(): void
    {
        $this->systemConfigService->set('foo.a', 'a');
        $actual = $this->systemConfigService->getDomain('foo.');
        static::assertEquals(['foo.a' => 'a'], $actual);
    }

    public function testDeleteNonExisting(): void
    {
        $this->systemConfigService->delete('not.found');
        $this->systemConfigService->delete('not.found', TestDefaults::SALES_CHANNEL);
    }

    public function testDelete(): void
    {
        $this->systemConfigService->set('foo', 'bar');
        $this->systemConfigService->set('foo', 'bar override', TestDefaults::SALES_CHANNEL);

        $this->systemConfigService->delete('foo');
        $actual = $this->systemConfigService->get('foo');
        static::assertNull($actual);
        $actual = $this->systemConfigService->get('foo', TestDefaults::SALES_CHANNEL);
        static::assertEquals('bar override', $actual);

        $this->systemConfigService->delete('foo', TestDefaults::SALES_CHANNEL);
        $actual = $this->systemConfigService->get('foo', TestDefaults::SALES_CHANNEL);
        static::assertNull($actual);
    }

    public function testGetDomainEmptyThrows(): void
    {
        $this->expectException(InvalidDomainException::class);
        $this->systemConfigService->getDomain('');
    }

    public function testGetDomainOnlySpacesThrows(): void
    {
        $this->expectException(InvalidDomainException::class);
        $this->systemConfigService->getDomain('     ');
    }

    public function testSetEmptyKeyThrows(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->systemConfigService->set('', 'throws error');
    }

    public function testSetOnlySpacesKeyThrows(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->systemConfigService->set('          ', 'throws error');
    }

    public function testSetInvalidSalesChannelThrows(): void
    {
        $this->expectException(InvalidUuidException::class);
        $this->systemConfigService->set('foo.bar', 'test', 'invalid uuid');
    }

    public function testWebhookEventsFired(): void
    {
        $eventDispatcher = static::getContainer()->get('event_dispatcher');

        $called = false;

        $this->addEventListener($eventDispatcher, SystemConfigChangedHook::class, function (SystemConfigChangedHook $event) use (&$called): void {
            static::assertEquals([
                'changes' => ['foo.bar'],
            ], $event->getWebhookPayload());

            $called = true;
        });

        $this->systemConfigService->set('foo.bar', 'test', TestDefaults::SALES_CHANNEL);

        static::assertTrue($called);
    }
}
