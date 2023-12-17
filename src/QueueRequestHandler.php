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
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use OCC\Basics\Traits\Getter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A queue-based PSR-15 HTTP Server Request Handler.
 *
 * @author Sebastian Meyer <sebastian.meyer@opencultureconsulting.com>
 * @package opencultureconsulting/psr15
 *
 * @property-read MiddlewareQueue $queue
 * @property-read ServerRequestInterface $request
 */
class QueueRequestHandler implements RequestHandlerInterface
{
    use Getter;

    /**
     * The PSR-7 HTTP Server Request.
     */
    protected ServerRequestInterface $request;

    /**
     * The queue of middlewares to process the server request.
     */
    protected MiddlewareQueue $queue;

    /**
     * The PSR-7 HTTP Response.
     */
    protected ResponseInterface $response;

    /**
     * Handles a request by invoking a queue of middlewares.
     *
     * @param ?ServerRequestInterface $request The PSR-7 server request to handle
     *
     * @return ResponseInterface A PSR-7 compatible HTTP response
     */
    public function handle(?ServerRequestInterface $request = null): ResponseInterface
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
            } catch(Exception $exception) {
                $options = [
                    'options' => [
                        'default' => 500,
                        'min_range' => 100,
                        'max_range' => 599
                    ]
                ];
                $statusCode = filter_var($exception->getCode(), FILTER_VALIDATE_INT, $options);
                $this->response = new Response(
                    $statusCode,
                    [],
                    sprintf(
                        'Exception thrown in middleware %s: %s',
                        get_debug_type($middleware),
                        $exception->getMessage()
                    )
                );
                $this->respond(1);
            }
        }
        return $this->response;
    }

    /**
     * Return the current response to the client.
     *
     * @param ?int $exitCode Exit code after sending out the response or NULL to continue
     *
     * @return void
     */
    public function respond(?int $exitCode = null): void
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
                'HTTP/%s %s %s',
                $this->response->getProtocolVersion(),
                $this->response->getStatusCode(),
                $this->response->getReasonPhrase()
            ),
            true
        );
        foreach (array_keys($this->response->getHeaders()) as $name) {
            $header = sprintf('%s: %s', $name, $this->response->getHeaderLine($name));
            header($header, false);
        }
        echo $this->response->getBody();
        if (!is_null($exitCode)) {
            exit($exitCode);
        }
    }

    /**
     * Magic getter method for $this->queue.
     * @see Getter
     *
     * @return MiddlewareQueue The queue of PSR-15 middlewares
     */
    protected function magicGetQueue(): MiddlewareQueue
    {
        return $this->queue;
    }

    /**
     * Magic getter method for $this->request.
     * @see Getter
     *
     * @return ServerRequestInterface The PSR-7 server request
     */
    protected function magicGetRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Magic getter method for $this->response.
     * @see Getter
     *
     * @return ResponseInterface The PSR-7 response
     */
    protected function magicGetResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Create a queue-based PSR-15 HTTP Server Request Handler.
     *
     * @param iterable<MiddlewareInterface> $middlewares Initial set of middlewares
     */
    public function __construct(iterable $middlewares = [])
    {
        $this->request = ServerRequest::fromGlobals();
        $this->queue = MiddlewareQueue::getInstance($middlewares);
        $this->response = new Response(200);
    }

    /**
     * Allow the request handler to be invoked directly.
     * @see QueueRequestHandler::handle()
     *
     * @param ?ServerRequestInterface $request The PSR-7 server request to handle
     *
     * @return ResponseInterface A PSR-7 compatible HTTP response
     */
    public function __invoke(?ServerRequestInterface $request = null): ResponseInterface
    {
        return $this->handle($request);
    }
}
