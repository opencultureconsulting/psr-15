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

namespace OCC\PSR15\Tests\Fixtures;

use Closure;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * A middleware delegating to a callback, e.g. to throw or short-circuit.
 */
final class CallbackMiddleware implements MiddlewareInterface
{
    /**
     * @param Closure(ServerRequest, RequestHandler): Response $callback
     */
    public function __construct(private readonly Closure $callback)
    {
    }

    public function process(ServerRequest $request, RequestHandler $handler): Response
    {
        return ($this->callback)($request, $handler);
    }
}
