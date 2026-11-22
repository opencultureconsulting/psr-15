<?php

/**
 * Queue-based PSR-15 HTTP Server Request Handler
 * Copyright (C) 2026 Sebastian Meyer <sebastian.meyer@opencultureconsulting.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace OCC\PSR15\Tests;

use DivisionByZeroError;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use OCC\PSR15\Defaults;
use OCC\PSR15\ErrorMiddleware;
use OCC\PSR15\MiddlewareQueue;
use OCC\PSR15\QueueRequestHandler;
use OCC\PSR15\Tests\Fixtures\CallbackMiddleware;
use OCC\PSR15\Tests\Fixtures\StubRequestHandler;
use OCC\PSR15\Tests\Fixtures\TrailMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

#[CoversClass(ErrorMiddleware::class)]
#[UsesClass(Defaults::class)]
#[UsesClass(MiddlewareQueue::class)]
#[UsesClass(QueueRequestHandler::class)]
final class ErrorMiddlewareTest extends TestCase
{
    /**
     * An exception message which must not be revealed to clients.
     */
    private const SECRET = 'Secret details';

    /**
     * The output of error_log() during the last call of captureErrorLog().
     */
    private string $errorLog = '';

    /**
     * Run the callback while writing the output of error_log() to $errorLog.
     *
     * This is done within the test (and not in setUp()), because some PHPUnit
     * versions redirect error_log() themselves before running a test.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T The return value of the callback
     */
    private function captureErrorLog(callable $callback): mixed
    {
        $logFile = (string) tempnam(sys_get_temp_dir(), 'psr15');
        $previousLog = ini_set('error_log', $logFile);
        try {
            return $callback();
        } finally {
            ini_set('error_log', (string) $previousLog);
            $this->errorLog = (string) file_get_contents($logFile);
            unlink($logFile);
        }
    }

    /**
     * Handle a request with an ErrorMiddleware and a middleware throwing the given error.
     */
    private function handleFailure(Throwable $error, ?ErrorMiddleware $errorMiddleware = null): ResponseInterface
    {
        $requestHandler = new QueueRequestHandler(
            [
                new TrailMiddleware('Outer'),
                $errorMiddleware ?? new ErrorMiddleware(),
                new TrailMiddleware('Inner'),
                new CallbackMiddleware(static fn () => throw $error)
            ],
            new ServerRequest('GET', '/failure')
        );
        return $this->captureErrorLog(static fn (): ResponseInterface => $requestHandler->handle());
    }

    #[Test]
    public function itPassesResponsesThroughUnchanged(): void
    {
        $handler = new StubRequestHandler();

        $response = $this->captureErrorLog(
            static fn (): ResponseInterface => (new ErrorMiddleware())->process(new ServerRequest('GET', '/'), $handler)
        );

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', $this->errorLog);
    }

    /**
     * @return array<string, array{Throwable, int, string}>
     */
    public static function errorProvider(): array
    {
        return [
            'server error code' => [new RuntimeException(self::SECRET, 503), 503, '503 Service Unavailable'],
            'client error code' => [new RuntimeException(self::SECRET, 404), 404, '404 Not Found'],
            'no code' => [new RuntimeException(self::SECRET), 500, '500 Internal Server Error'],
            'redirect code' => [new RuntimeException(self::SECRET, 302), 500, '500 Internal Server Error'],
            'success code' => [new RuntimeException(self::SECRET, 200), 500, '500 Internal Server Error'],
            'invalid code' => [new RuntimeException(self::SECRET, 1234), 500, '500 Internal Server Error'],
            'PHP error' => [new DivisionByZeroError(self::SECRET), 500, '500 Internal Server Error']
        ];
    }

    #[Test]
    #[DataProvider('errorProvider')]
    public function itConvertsErrorsIntoGenericResponses(Throwable $error, int $status, string $body): void
    {
        $response = $this->handleFailure($error);

        $this->assertSame($status, $response->getStatusCode());
        $this->assertSame($body, (string) $response->getBody());
        $this->assertSame('text/plain; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertStringNotContainsString(self::SECRET, (string) $response->getBody());
        $this->assertFalse($response->hasHeader('Warning'));
    }

    #[Test]
    public function middlewaresBeforeItReceiveTheErrorResponse(): void
    {
        $response = $this->handleFailure(new RuntimeException('Failure'));

        // "Inner" is skipped by the exception, "Outer" processes the error response.
        $this->assertSame(['Outer'], $response->getHeader('X-Trail'));
    }

    #[Test]
    public function itLogsTheDetails(): void
    {
        $error = new RuntimeException('Secret details', 503);

        $this->handleFailure($error);

        $this->assertStringContainsString(
            sprintf('Uncaught RuntimeException: Secret details in %s on line %d', $error->getFile(), $error->getLine()),
            $this->errorLog
        );
    }

    #[Test]
    public function itUsesTheCustomErrorHandler(): void
    {
        $error = new RuntimeException('Failure');
        $received = [];
        $errorMiddleware = new ErrorMiddleware(
            static function (Throwable $error, ServerRequestInterface $request) use (&$received): ResponseInterface {
                $received = [$error, $request->getUri()->getPath()];
                return new Response(418, [], 'Custom');
            }
        );

        $response = $this->handleFailure($error, $errorMiddleware);

        $this->assertSame([$error, '/failure'], $received);
        $this->assertSame(418, $response->getStatusCode());
        $this->assertSame('Custom', (string) $response->getBody());
        $this->assertSame('', $this->errorLog);
    }

    #[Test]
    public function exceptionsOfTheCustomErrorHandlerAreNotCaught(): void
    {
        $handlerException = new RuntimeException('Handler failed');
        $errorMiddleware = new ErrorMiddleware(static fn () => throw $handlerException);

        try {
            $this->handleFailure(new RuntimeException('Failure'), $errorMiddleware);
            $this->fail('Exception of the error handler was caught.');
        } catch (RuntimeException $caught) {
            $this->assertSame($handlerException, $caught);
        }
    }

    #[Test]
    public function itUsesTheGivenResponseFactory(): void
    {
        $factory = new class () implements ResponseFactoryInterface {
            public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
            {
                return (new HttpFactory())->createResponse($code, $reasonPhrase)->withHeader('X-Factory', 'custom');
            }
        };

        $response = $this->handleFailure(new RuntimeException('Failure'), new ErrorMiddleware(null, $factory));

        $this->assertSame('custom', $response->getHeaderLine('X-Factory'));
        $this->assertSame(500, $response->getStatusCode());
    }
}
