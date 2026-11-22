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

use LogicException;
use OCC\Basics\Traits\Getter;
use Override;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use RuntimeException;

use function count;
use function header;
use function headers_sent;
use function sprintf;
use function strtolower;

/**
 * A queue-based PSR-15 HTTP Server Request Handler.
 *
 * @author Sebastian Meyer <sebastian.meyer@opencultureconsulting.com>
 * @package PSR15
 *
 * @property-read MiddlewareQueue $queue
 * @property-read ServerRequest $request
 * @property-read Response $response
 */
final class QueueRequestHandler implements RequestHandler
{
    use Getter;

    /**
     * The queue of middlewares to process the server request.
     *
     * @internal
     */
    private readonly MiddlewareQueue $queue;

    /**
     * The PSR-7 HTTP Server Request.
     *
     * @internal
     */
    private ServerRequest $request;

    /**
     * The PSR-7 HTTP Response.
     *
     * @internal
     */
    private Response $response;

    /**
     * The server request used when handle() is called without one.
     *
     * @internal
     */
    private readonly ServerRequest $initialRequest;

    /**
     * The copy of the queue used while a request is being handled.
     *
     * @internal
     */
    private ?MiddlewareQueue $currentQueue = null;

    /**
     * The PSR-17 factory creating the initial response for each request.
     *
     * @internal
     */
    private readonly ResponseFactory $responseFactory;

    /**
     * Handles a request by invoking a queue of middlewares.
     *
     * Each top-level call works on its own copy of the queue, so the same handler
     * can process any number of requests. Changes to the queue made while handling
     * a request only apply to that request.
     *
     * Exceptions thrown by a middleware are not caught, but passed through all
     * previous middlewares to the caller. Add an ErrorMiddleware as the first
     * middleware to convert them into responses.
     *
     * @param ?ServerRequest $request The PSR-7 server request to handle
     *
     * @return Response A PSR-7 compatible HTTP response
     *
     * @api
     */
    #[Override]
    public function handle(?ServerRequest $request = null): Response
    {
        // A middleware delegating to the next one keeps the current request
        // as default, a new top-level call starts over with a clean state.
        $this->request = $request ?? $this->request;
        $isNewRun = $this->currentQueue === null;
        if ($isNewRun) {
            $this->currentQueue = clone $this->queue;
            $this->request = $request ?? $this->initialRequest;
            $this->response = $this->responseFactory->createResponse();
        }
        try {
            $this->processNextMiddleware();
        } finally {
            if ($isNewRun) {
                $this->currentQueue = null;
            }
        }
        return $this->response;
    }

    /**
     * Passes the current request to the next middleware in the current queue.
     *
     * @return void
     *
     * @internal
     */
    private function processNextMiddleware(): void
    {
        $queue = $this->currentQueue;
        if ($queue === null || count($queue) === 0) {
            return;
        }
        // It is RECOMMENDED that any application using middleware includes a
        // component that catches exceptions and converts them into responses.
        // This middleware SHOULD be the first component executed and wrap all
        // further processing to ensure that a response is always generated.
        // You can use the ErrorMiddleware included in this package for this.
        $this->response = $queue->dequeue()->process($this->request, $this);
    }

    /**
     * Return the current response to the client.
     *
     * @return void
     *
     * @throws RuntimeException if headers were already sent
     *
     * @psalm-suppress TaintedInput
     *
     * @api
     */
    public function respond(): void
    {
        $file = 'unknown file';
        $line = 0;
        if (headers_sent($file, $line)) {
            throw new RuntimeException(
                sprintf(
                    'Headers already sent in %s on line %d',
                    $file,
                    $line
                ),
                500
            );
        }
        foreach ($this->response->getHeaders() as $name => $values) {
            $name = (string) $name;
            // Send every value as a separate header line, because some headers
            // (e.g. "Set-Cookie") must not be combined into one comma-separated
            // line. The first line replaces any header of the same name already
            // set by PHP, except for cookies which might have been set elsewhere.
            $replace = strtolower($name) !== 'set-cookie';
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), $replace);
                $replace = false;
            }
        }
        header(
            sprintf(
                'HTTP/%s %d %s',
                $this->response->getProtocolVersion(),
                $this->response->getStatusCode(),
                $this->response->getReasonPhrase()
            ),
            true
        );
        echo $this->response->getBody();
    }

    /**
     * Magic getter method for $this->queue.
     *
     * While a request is being handled this returns the copy of the queue used
     * for that request, otherwise the queue the handler was configured with.
     *
     * @return MiddlewareQueue The queue of PSR-15 middlewares
     *
     * @internal
     */
    protected function _magicGetQueue(): MiddlewareQueue
    {
        return $this->currentQueue ?? $this->queue;
    }

    /**
     * Magic getter method for $this->request.
     *
     * @return ServerRequest The PSR-7 server request
     *
     * @internal
     */
    protected function _magicGetRequest(): ServerRequest
    {
        return $this->request;
    }

    /**
     * Magic getter method for $this->response.
     *
     * @return Response The PSR-7 response
     *
     * @internal
     */
    protected function _magicGetResponse(): Response
    {
        return $this->response;
    }

    /**
     * Create a queue-based PSR-15 HTTP Server Request Handler.
     *
     * @param iterable<array-key, Middleware> $middlewares Initial set of middlewares
     * @param ?ServerRequest $request The server request to handle by default (defaults to the request created from
     *        PHP's superglobals by Guzzle)
     * @param ?ResponseFactory $responseFactory The PSR-17 factory creating the initial response for each request
     *        (defaults to Guzzle's factory)
     *
     * @throws LogicException if a default is needed, but Guzzle is not installed
     */
    public function __construct(
        iterable $middlewares = [],
        ?ServerRequest $request = null,
        ?ResponseFactory $responseFactory = null
    ) {
        $this->initialRequest = $request ?? Defaults::serverRequest(self::class);
        $this->request = $this->initialRequest;
        $this->queue = new MiddlewareQueue($middlewares);
        $this->responseFactory = $responseFactory ?? Defaults::responseFactory(self::class);
        $this->response = $this->responseFactory->createResponse();
    }

    /**
     * Allow the request handler to be invoked directly.
     *
     * @param ?ServerRequest $request The PSR-7 server request to handle
     *
     * @return Response A PSR-7 compatible HTTP response
     */
    public function __invoke(?ServerRequest $request = null): Response
    {
        return $this->handle($request);
    }
}
