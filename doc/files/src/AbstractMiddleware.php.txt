<?php

/**
 * Queue-based PSR-15 HTTP Server Request Handler
 * Copyright (C) 2023 Sebastian Meyer <sebastian.meyer@opencultureconsulting.com>
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

namespace OCC\PSR15;

use DomainException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Abstract class implementing \Psr\Http\Server\MiddlewareInterface.
 *
 * @author Sebastian Meyer <sebastian.meyer@opencultureconsulting.com>
 * @package PSR15
 */
abstract class AbstractMiddleware implements MiddlewareInterface
{
    /**
     * The PSR-15 Server Request Handler.
     *
     * @internal
     */
    protected QueueRequestHandler $requestHandler;

    /**
     * Process an incoming server request and produce a response.
     *
     * @param ServerRequest $request The server request to process
     * @param RequestHandler $handler The request handler to delegate to
     *
     * @return Response The response object
     *
     * @throws DomainException if request handler is not a QueueRequestHandler
     *
     * @api
     */
    #[\Override]
    final public function process(ServerRequest $request, RequestHandler $handler): Response
    {
        if (!$handler instanceof QueueRequestHandler) {
            throw new DomainException(
                sprintf(
                    'Expected instance of %s, got %s',
                    QueueRequestHandler::class,
                    get_debug_type($handler)
                ),
                500
            );
        }
        $this->requestHandler = $handler;
        // Manipulate request if necessary.
        $request = $this->processRequest($request);
        // Delegate request to next middleware and get response.
        $response = $this->requestHandler->handle($request);
        // Manipulate response if necessary.
        $response = $this->processResponse($response);
        // Return response to previous middleware.
        return $response;
    }

    /**
     * Process an incoming server request before delegating to next middleware.
     *
     * @param ServerRequest $request The incoming server request
     *
     * @return ServerRequest The processed server request
     */
    protected function processRequest(ServerRequest $request): ServerRequest
    {
        return $request;
    }

    /**
     * Process an incoming response before returning it to previous middleware.
     *
     * @param Response $response The incoming response
     *
     * @return Response The processed response
     */
    protected function processResponse(Response $response): Response
    {
        return $response;
    }

    /**
     * Allow the middleware to be invoked directly.
     *
     * @param ServerRequest $request The server request to process
     * @param RequestHandler $handler The request handler to delegate to
     *
     * @return Response The response object
     */
    final public function __invoke(ServerRequest $request, RequestHandler $handler): Response
    {
        return $this->process($request, $handler);
    }
}
