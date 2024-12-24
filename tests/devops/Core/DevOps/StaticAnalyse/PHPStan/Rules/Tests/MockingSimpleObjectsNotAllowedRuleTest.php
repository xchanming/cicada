<?php

declare(strict_types=1);

namespace Cicada\Tests\DevOps\Core\DevOps\StaticAnalyse\PHPStan\Rules\Tests;

use Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules\Tests\MockingSimpleObjectsNotAllowedRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @internal
 *
 * @extends  RuleTestCase<MockingSimpleObjectsNotAllowedRule>
 */
class MockingSimpleObjectsNotAllowedRuleTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/../data/MockingSimpleObjects/cicada-unit-test.php'], [
            [
                'Mocking of Cicada\Core\Checkout\Order\OrderEntity is not allowed. The object is very basic and can be constructed',
                16,
            ],
        ]);

        $this->analyse([__DIR__ . '/../data/MockingSimpleObjects/commercial-unit-test.php'], [
            [
                'Mocking of Cicada\Core\Checkout\Order\OrderEntity is not allowed. The object is very basic and can be constructed',
                16,
            ],
        ]);

        $this->analyse([__DIR__ . '/../data/MockingSimpleObjects/parent-class-test.php'], [
            [
                'Mocking of Cicada\Core\Checkout\Order\OrderEntity is not allowed. The object is very basic and can be constructed',
                14,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        return new MockingSimpleObjectsNotAllowedRule(self::createReflectionProvider());
    }
}
