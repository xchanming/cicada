<?php
declare(strict_types=1);

namespace Cicada\WebInstaller\Tests\Services;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\WebInstaller\Services\StreamedCommandResponseGenerator;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
#[CoversClass(StreamedCommandResponseGenerator::class)]
class StreamedCommandResponseGeneratorTest extends TestCase
{
    public function testRun(): void
    {
        $generator = new StreamedCommandResponseGenerator();

        $response = $generator->run(['echo', 'foo'], function (Process $process): void {
            static::assertTrue($process->isSuccessful());
        });

        ob_start();
        $response->sendContent();

        $content = ob_get_clean();

        static::assertSame('foo', trim((string) $content));
    }

    public function testRunJSON(): void
    {
        $generator = new StreamedCommandResponseGenerator();

        $response = $generator->runJSON(['echo', 'foo']);

        ob_start();
        $response->sendContent();

        $content = ob_get_clean();

        static::assertSame('foo' . \PHP_EOL . '{"success":true}', $content);
    }

    public function testCustomTimeoutFromEnv(): void
    {
        $customTimeout = 333.0;
        putenv('CICADA_INSTALLER_TIMEOUT=' . $customTimeout);

        $generator = new StreamedCommandResponseGenerator();

        $theFinishedProcess = null;
        $response = $generator->runJSON(['echo', 'foo'], function (Process $process) use (&$theFinishedProcess): void {
            $theFinishedProcess = $process;
        });

        ob_start();
        $response->sendContent();
        ob_end_clean();

        static::assertNotNull($theFinishedProcess);
        static::assertSame((float) $customTimeout, $theFinishedProcess->getTimeout());

        // Cleanup
        putenv('CICADA_INSTALLER_TIMEOUT');
    }

    public function testDefaultTimeout(): void
    {
        // Ensure no timeout is set in environment
        putenv('CICADA_INSTALLER_TIMEOUT');

        $generator = new StreamedCommandResponseGenerator();

        $theFinishedProcess = null;
        $response = $generator->runJSON(['echo', 'foo'], function (Process $process) use (&$theFinishedProcess): void {
            $theFinishedProcess = $process;
        });

        ob_start();
        $response->sendContent();
        ob_end_clean();

        static::assertNotNull($theFinishedProcess);
        static::assertSame(StreamedCommandResponseGenerator::DEFAULT_TIMEOUT, $theFinishedProcess->getTimeout());
    }

    public function testNonNumericTimeoutUsesDefault(): void
    {
        putenv('CICADA_INSTALLER_TIMEOUT=not-a-number');

        $generator = new StreamedCommandResponseGenerator();

        $theFinishedProcess = null;
        $response = $generator->runJSON(['echo', 'foo'], function (Process $process) use (&$theFinishedProcess): void {
            $theFinishedProcess = $process;
        });

        ob_start();
        $response->sendContent();
        ob_end_clean();

        static::assertNotNull($theFinishedProcess);
        static::assertSame(StreamedCommandResponseGenerator::DEFAULT_TIMEOUT, $theFinishedProcess->getTimeout());

        // Cleanup
        putenv('CICADA_INSTALLER_TIMEOUT');
    }

    public function testNegativeTimeoutUsesDefault(): void
    {
        putenv('CICADA_INSTALLER_TIMEOUT=-42.5');

        $generator = new StreamedCommandResponseGenerator();

        $theFinishedProcess = null;
        $response = $generator->runJSON(['echo', 'foo'], function (Process $process) use (&$theFinishedProcess): void {
            $theFinishedProcess = $process;
        });

        ob_start();
        $response->sendContent();
        ob_end_clean();

        static::assertNotNull($theFinishedProcess);
        static::assertSame(StreamedCommandResponseGenerator::DEFAULT_TIMEOUT, $theFinishedProcess->getTimeout());

        // Cleanup
        putenv('CICADA_INSTALLER_TIMEOUT');
    }
}
