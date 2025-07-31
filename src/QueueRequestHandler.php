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

use Exception;
use RuntimeException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Psr7\ServerRequest as GuzzleRequest;
use OCC\Basics\Traits\Getter;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use function array_keys;
use function count;
use function filter_var;
use function get_debug_type;
use function header;
use function headers_sent;
use function sprintf;

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
     * The PSR-7 HTTP Server Request.
     *
     * @internal
     */
    protected ServerRequest $request;

    /**
     * The queue of middlewares to process the server request.
     *
     * @internal
     */
    protected MiddlewareQueue $queue;

    /**
     * The PSR-7 HTTP Response.
     *
     * @internal
     */
    protected Response $response;

    /**
     * Handles a request by invoking a queue of middlewares.
     *
     * @param ?ServerRequest $request The PSR-7 server request to handle
     *
     * @return Response A PSR-7 compatible HTTP response
     *
     * @api
     */
    #[\Override]
    public function handle(?ServerRequest $request = null): Response
    {
        $this->request = $request ?? $this->request;
        if (count($this->queue) > 0) {
            $middleware = $this->queue->dequeue();
            // It is RECOMMENDED that any application using middleware includes a
            // component that catches exceptions and converts them into responses.
            // This middleware SHOULD be the first component executed and wrap all
            // further processing to ensure that a response is always generated.
            try {
                $this->response = $middleware->process($this->request, $this);
            } catch (Exception $exception) {
                $options = [
                    'options' => [
                        'default' => 500,
                        'min_range' => 100,
                        'max_range' => 599
                    ]
                ];
                $this->response = new GuzzleResponse(
                    filter_var($exception->getCode(), FILTER_VALIDATE_INT, $options),
                    [
                        'Warning' => [sprintf(
                            'Error %d in %s',
                            $exception->getCode(),
                            get_debug_type($middleware)
                        )]
                    ],
                    sprintf(
                        'Exception %d thrown in middleware %s: %s',
                        $exception->getCode(),
                        get_debug_type($middleware),
                        $exception->getMessage()
                    )
                );
            }
        }
        return $this->response;
    }

    /**
     * Return the current response to the client.
     *
     * @return void
     *
     * @throws RuntimeException if headers were already sent
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
                )
            );
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
        foreach (array_keys($this->response->getHeaders()) as $name) {
            /** @var string $name */
            $header = sprintf('%s: %s', $name, $this->response->getHeaderLine($name));
            header($header, false);
        }
        echo $this->response->getBody();
    }

    /**
     * Magic getter method for $this->queue.
     *
     * @return MiddlewareQueue The queue of PSR-15 middlewares
     *
     * @internal
     */
    protected function _magicGetQueue(): MiddlewareQueue
    {
        return $this->queue;
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
     *
     * @return void
     */
    public function __construct(iterable $middlewares = [])
    {
        $this->request = GuzzleRequest::fromGlobals();
        $this->queue = MiddlewareQueue::getInstance($middlewares);
        $this->response = new GuzzleResponse();
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
