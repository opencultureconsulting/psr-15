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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Abstract class implementing Psr\Http\Server\MiddlewareInterface.
 *
 * @author Sebastian Meyer <sebastian.meyer@opencultureconsulting.com>
 * @package opencultureconsulting/psr15
 */
abstract class AbstractMiddleware implements MiddlewareInterface
{
    /**
     * The PSR-15 Server Request Handler.
     */
    protected RequestHandlerInterface $handler;

    /**
     * The PSR-7 HTTP Server Request after processing.
     */
    protected ServerRequestInterface $request;

    /**
     * The PSR-7 HTTP Response after processing.
     */
    protected ResponseInterface $response;

    /**
     * Process an incoming server request and produce a response.
     * @see MiddlewareInterface::process()
     *
     * @param ServerRequestInterface $request The server request to process
     * @param RequestHandlerInterface $handler The request handler to delegate to
     *
     * @return ResponseInterface The response object
     */
    final public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->handler = $handler;
        // Manipulate request if necessary.
        $this->request = $this->processRequest($request);
        // Delegate request to next middleware and get response.
        $response = $handler->handle($this->request);
        // Manipulate response if necessary.
        $this->response = $this->processResponse($response);
        // Return response to previous middleware.
        return $this->response;
    }

    /**
     * Process an incoming server request before delegating to next middleware.
     *
     * @param ServerRequestInterface $request The incoming server request
     *
     * @return ServerRequestInterface The processed server request
     */
    abstract protected function processRequest(ServerRequestInterface $request): ServerRequestInterface;

    /**
     * Process an incoming response before returning it to previous middleware.
     *
     * @param ResponseInterface $response The incoming response
     *
     * @return ResponseInterface The processed response
     */
    abstract protected function processResponse(ResponseInterface $response): ResponseInterface;

    /**
     * Allow the middleware to be invoked directly.
     * @see AbstractMiddleware::process()
     *
     * @param ServerRequestInterface $request The server request to process
     * @param RequestHandlerInterface $handler The request handler to delegate to
     *
     * @return ResponseInterface The response object
     */
    final public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->process($request, $handler);
    }
}
