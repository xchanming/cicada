<?php declare(strict_types=1);

namespace Cicada\Core\Test\Stub\EventDispatcher;

use Cicada\Core\Framework\Test\TestCaseHelper\CallableClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AssertingEventDispatcher extends EventDispatcher
{
    /**
     * @param array<string, int> $assertions
     */
    public function __construct(TestCase $test, array $assertions)
    {
        foreach ($assertions as $event => $count) {
            $listener = $test->getMockBuilder(CallableClass::class)->getMock();
            $listener
                ->expects(TestCase::exactly($count))
                ->method('__invoke');

            $this->addListener($event, $listener);
        }
    }
}
