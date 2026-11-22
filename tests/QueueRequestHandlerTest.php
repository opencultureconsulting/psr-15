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

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use OCC\PSR15\Defaults;
use OCC\PSR15\MiddlewareQueue;
use OCC\PSR15\QueueRequestHandler;
use OCC\PSR15\Tests\Fixtures\CallbackMiddleware;
use OCC\PSR15\Tests\Fixtures\TrailMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use TypeError;

#[CoversClass(QueueRequestHandler::class)]
#[UsesClass(Defaults::class)]
#[UsesClass(MiddlewareQueue::class)]
final class QueueRequestHandlerTest extends TestCase
{
    #[Test]
    public function itReturnsBlankResponseForEmptyQueue(): void
    {
        $response = (new QueueRequestHandler([], new ServerRequest('GET', '/')))->handle();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
    }

    #[Test]
    public function eachHandlerHasItsOwnQueue(): void
    {
        $first = new QueueRequestHandler([new TrailMiddleware('A')], new ServerRequest('GET', '/'));
        $second = new QueueRequestHandler([new TrailMiddleware('B')], new ServerRequest('GET', '/'));

        $this->assertNotSame($first->queue, $second->queue);
        $this->assertSame('A', $first->handle()->getHeaderLine('X-Trail'));
        $this->assertSame('B', $second->handle()->getHeaderLine('X-Trail'));
    }

    #[Test]
    public function itCanHandleMultipleRequests(): void
    {
        $requestHandler = new QueueRequestHandler(
            [new TrailMiddleware('A'), new TrailMiddleware('B')],
            new ServerRequest('GET', '/')
        );

        foreach (['/one', '/two', '/three'] as $path) {
            $response = $requestHandler->handle(new ServerRequest('GET', $path));
            $this->assertSame(['B', 'A'], $response->getHeader('X-Trail'));
            $this->assertSame($path, $requestHandler->request->getUri()->getPath());
            $this->assertSame('AB', $requestHandler->request->getAttribute('trail'));
        }
        $this->assertCount(2, $requestHandler->queue);
    }

    #[Test]
    public function itUsesTheInitialRequestByDefault(): void
    {
        $initialRequest = new ServerRequest('GET', '/initial');
        $requestHandler = new QueueRequestHandler([new TrailMiddleware('A')], $initialRequest);

        $requestHandler->handle(new ServerRequest('GET', '/other'));
        $requestHandler->handle();

        $this->assertSame('/initial', $requestHandler->request->getUri()->getPath());
    }

    #[Test]
    public function itCanBeInvokedDirectly(): void
    {
        $requestHandler = new QueueRequestHandler([new TrailMiddleware('A')], new ServerRequest('GET', '/'));

        $this->assertSame('A', $requestHandler(new ServerRequest('GET', '/'))->getHeaderLine('X-Trail'));
    }

    #[Test]
    public function aShortCircuitDoesNotAffectTheNextRequest(): void
    {
        $gate = new CallbackMiddleware(
            static fn (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface =>
                $request->getUri()->getPath() === '/stop' ? new Response(403) : $handler->handle($request)
        );
        $requestHandler = new QueueRequestHandler(
            [new TrailMiddleware('A'), $gate, new TrailMiddleware('B')],
            new ServerRequest('GET', '/')
        );

        $this->assertSame(['A'], $requestHandler->handle(new ServerRequest('GET', '/stop'))->getHeader('X-Trail'));
        $this->assertSame(403, $requestHandler->response->getStatusCode());

        $response = $requestHandler->handle(new ServerRequest('GET', '/'));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['B', 'A'], $response->getHeader('X-Trail'));
    }

    #[Test]
    public function changesToTheQueueDuringARequestOnlyApplyToThatRequest(): void
    {
        $mutator = new CallbackMiddleware(
            static function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
                if ($handler instanceof QueueRequestHandler) {
                    match ($request->getUri()->getPath()) {
                        '/add' => $handler->queue->enqueue(new TrailMiddleware('X')),
                        '/skip' => $handler->queue->dequeue(),
                        default => null
                    };
                }
                return $handler->handle($request);
            }
        );
        $requestHandler = new QueueRequestHandler(
            [new TrailMiddleware('A'), $mutator, new TrailMiddleware('B')],
            new ServerRequest('GET', '/')
        );

        $trail = static fn (string $path): array =>
            $requestHandler->handle(new ServerRequest('GET', $path))->getHeader('X-Trail');

        $this->assertSame(['X', 'B', 'A'], $trail('/add'));
        $this->assertSame(['A'], $trail('/skip'));
        $this->assertSame(['B', 'A'], $trail('/'));
        $this->assertCount(3, $requestHandler->queue);
    }

    #[Test]
    public function exceptionsArePassedThroughToTheCaller(): void
    {
        $exception = new RuntimeException('Failure', 503);
        $requestHandler = new QueueRequestHandler(
            [new TrailMiddleware('A'), new CallbackMiddleware(static fn () => throw $exception)],
            new ServerRequest('GET', '/')
        );

        try {
            $requestHandler->handle();
            $this->fail('Exception was not passed through.');
        } catch (RuntimeException $caught) {
            $this->assertSame($exception, $caught);
        }
    }

    #[Test]
    public function errorsArePassedThroughToTheCaller(): void
    {
        $requestHandler = new QueueRequestHandler(
            [new CallbackMiddleware(static fn () => throw new TypeError('Wrong type'))],
            new ServerRequest('GET', '/')
        );

        $this->expectException(TypeError::class);
        $requestHandler->handle();
    }

    #[Test]
    public function middlewaresCanCatchExceptionsThemselves(): void
    {
        $recover = new CallbackMiddleware(
            static function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
                try {
                    return $handler->handle($request);
                } catch (RuntimeException $exception) {
                    return new Response(299, [], $exception->getMessage());
                }
            }
        );
        $requestHandler = new QueueRequestHandler(
            [$recover, new CallbackMiddleware(static fn () => throw new RuntimeException('Recovered'))],
            new ServerRequest('GET', '/')
        );

        $response = $requestHandler->handle();

        $this->assertSame(299, $response->getStatusCode());
        $this->assertSame('Recovered', (string) $response->getBody());
    }

    #[Test]
    public function itCanHandleRequestsAfterAnException(): void
    {
        $requestHandler = new QueueRequestHandler(
            [
                new TrailMiddleware('A'),
                new CallbackMiddleware(
                    static fn (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface =>
                        $request->getUri()->getPath() === '/fail'
                            ? throw new RuntimeException('Failure')
                            : $handler->handle($request)
                ),
                new TrailMiddleware('B')
            ],
            new ServerRequest('GET', '/')
        );

        try {
            $requestHandler->handle(new ServerRequest('GET', '/fail'));
        } catch (RuntimeException) {
            // Expected, see exceptionsArePassedThroughToTheCaller().
        }

        $this->assertSame(['B', 'A'], $requestHandler->handle(new ServerRequest('GET', '/'))->getHeader('X-Trail'));
        $this->assertCount(3, $requestHandler->queue);
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
        $requestHandler = new QueueRequestHandler([], new ServerRequest('GET', '/'), $factory);

        $this->assertSame('custom', $requestHandler->response->getHeaderLine('X-Factory'));
        $this->assertSame('custom', $requestHandler->handle()->getHeaderLine('X-Factory'));
    }

    #[Test]
    public function itCreatesTheRequestFromGlobalsByDefault(): void
    {
        $this->assertInstanceOf(ServerRequestInterface::class, (new QueueRequestHandler())->request);
    }
}
