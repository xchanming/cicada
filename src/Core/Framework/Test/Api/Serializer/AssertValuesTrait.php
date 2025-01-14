<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Api\Serializer;

use PHPUnit\Framework\TestCase;

trait AssertValuesTrait
{
    /**
     * @param array<int|string, mixed> $expected
     * @param array<int|string, mixed> $actual
     */
    protected function assertValues(array $expected, array $actual): void
    {
        foreach ($expected as $key => $value) {
            TestCase::assertArrayHasKey($key, $actual);

            if (\is_array($value)) {
                $this->assertValues($value, $actual[$key]);
            } else {
                TestCase::assertEquals($value, $actual[$key]);
            }
        }
    }
}
