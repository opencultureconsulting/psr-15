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

use GuzzleHttp\Psr7\ServerRequest;
use OCC\PSR15\AbstractMiddleware;
use OCC\PSR15\QueueRequestHandler;
use OCC\PSR15\Tests\Fixtures\StubRequestHandler;
use OCC\PSR15\Tests\Fixtures\TrailMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractMiddleware::class)]
final class AbstractMiddlewareTest extends TestCase
{
    #[Test]
    public function itWorksWithAnyRequestHandler(): void
    {
        $handler = new StubRequestHandler();

        $response = (new TrailMiddleware('A'))->process(new ServerRequest('GET', '/'), $handler);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('A', $handler->lastRequest?->getAttribute('trail'));
        $this->assertSame('A', $response->getHeaderLine('X-Trail'));
    }

    #[Test]
    public function itPassesRequestAndResponseThroughUnchangedByDefault(): void
    {
        $request = new ServerRequest('GET', '/');
        $handler = new StubRequestHandler();
        $middleware = new class () extends AbstractMiddleware {
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame($request, $handler->lastRequest);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame([], $response->getHeaders());
    }

    #[Test]
    public function itCanBeInvokedDirectly(): void
    {
        $handler = new StubRequestHandler();

        $response = (new TrailMiddleware('A'))(new ServerRequest('GET', '/'), $handler);

        $this->assertSame('A', $response->getHeaderLine('X-Trail'));
    }

    #[Test]
    public function requestsAndResponsesAreProcessedInOppositeOrder(): void
    {
        $requestHandler = new QueueRequestHandler(
            [new TrailMiddleware('A'), new TrailMiddleware('B'), new TrailMiddleware('C')],
            new ServerRequest('GET', '/')
        );

        $response = $requestHandler->handle();

        $this->assertSame('ABC', $requestHandler->request->getAttribute('trail'));
        $this->assertSame(['C', 'B', 'A'], $response->getHeader('X-Trail'));
    }
}
