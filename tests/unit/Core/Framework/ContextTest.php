<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayEntity;
use Cicada\Core\Framework\Struct\Serializer\StructNormalizer;
use Cicada\Core\Framework\Uuid\Uuid;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(Context::class)]
class ContextTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $context = Context::createDefaultContext();

        static::assertInstanceOf(SystemSource::class, $context->getSource());
        static::assertEquals(Context::SYSTEM_SCOPE, $context->getScope());
        static::assertEquals([], $context->getRuleIds());
        static::assertEquals(Defaults::LIVE_VERSION, $context->getVersionId());
    }

    public function testScope(): void
    {
        $context = Context::createDefaultContext();

        static::assertEquals(Context::SYSTEM_SCOPE, $context->getScope());

        $context->scope('foo', function (Context $context): void {
            static::assertEquals('foo', $context->getScope());
        });

        static::assertEquals(Context::SYSTEM_SCOPE, $context->getScope());
    }

    public function testVersionChange(): void
    {
        $versionId = Uuid::randomHex();

        $context = Context::createDefaultContext();
        $versionContext = $context->createWithVersionId($versionId);

        static::assertEquals(Defaults::LIVE_VERSION, $context->getVersionId());
        static::assertEquals($versionId, $versionContext->getVersionId());
    }

    public function testVersionChangeInheritsExtensions(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension('foo', new ArrayEntity());

        static::assertNotNull($context->getExtension('foo'));

        $versionContext = $context->createWithVersionId(Uuid::randomHex());

        static::assertNotNull($versionContext->getExtension('foo'));
    }

    public function testExtensionsAreStripped(): void
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $discriminator = new ClassDiscriminatorFromClassMetadata($classMetadataFactory);

        $normalizers = [new StructNormalizer(), new ObjectNormalizer($classMetadataFactory, null, null, null, $discriminator), new ArrayDenormalizer()];
        $serializer = new Serializer($normalizers, [new JsonEncoder()]);

        $context = Context::createDefaultContext();

        $context->addExtension('foo', new ArrayEntity());

        $serialized = $serializer->serialize($context, 'json');
        $deserialized = $serializer->deserialize($serialized, Context::class, 'json');

        static::assertInstanceOf(Context::class, $deserialized);

        static::assertEmpty($deserialized->getVars()['extensions']);
    }
}
