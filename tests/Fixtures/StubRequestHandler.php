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

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * A minimal request handler not belonging to this package, which remembers
 * the last request it handled.
 */
final class StubRequestHandler implements RequestHandler
{
    public ?ServerRequest $lastRequest = null;

    public function handle(ServerRequest $request): Response
    {
        $this->lastRequest = $request;
        return new GuzzleResponse(204);
    }
}
