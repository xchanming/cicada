<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Filesystem\Adapter;

use Cicada\Core\Framework\Adapter\Filesystem\Adapter\GoogleStorageFactory;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GoogleStorageFactory::class)]
class GoogleStorageFactoryTest extends TestCase
{
    public function testCreateGoogleStorageFromConfigString(): void
    {
        $googleStorageFactory = new GoogleStorageFactory();
        static::assertSame('google-storage', $googleStorageFactory->getType());

        /** @var string $key */
        $key = file_get_contents(__DIR__ . '/fixtures/keyfile.json');

        $config = [
            'projectId' => 'TestGoogleStorage',
            'keyFile' => json_decode($key, true, 512, \JSON_THROW_ON_ERROR),
            'bucket' => 'TestBucket',
            'root' => '/',
        ];

        try {
            static::assertInstanceOf(GoogleCloudStorageAdapter::class, $googleStorageFactory->create($config));
        } catch (\Exception $e) {
            static::fail($e->getMessage());
        }
    }

    public function testCreateGoogleStorageFromConfigFile(): void
    {
        $googleStorageFactory = new GoogleStorageFactory();

        $config = [
            'projectId' => 'TestGoogleStorage',
            'keyFilePath' => __DIR__ . '/fixtures/keyfile.json',
            'bucket' => 'TestBucket',
            'root' => '/',
        ];

        try {
            static::assertInstanceOf(GoogleCloudStorageAdapter::class, $googleStorageFactory->create($config));
        } catch (\Exception $e) {
            static::fail($e->getMessage());
        }
    }
}
