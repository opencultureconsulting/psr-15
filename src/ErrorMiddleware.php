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

namespace OCC\PSR15;

use Closure;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Override;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Throwable;

use function error_log;
use function filter_var;
use function get_debug_type;
use function sprintf;

/**
 * A PSR-15 middleware converting exceptions and errors into responses.
 *
 * As recommended by PSR-15, this middleware should be the first one in the
 * queue, so it wraps all further processing and a response is always generated.
 *
 * @author Sebastian Meyer <sebastian.meyer@opencultureconsulting.com>
 * @package PSR15
 */
final class ErrorMiddleware implements MiddlewareInterface
{
    /**
     * The callback converting exceptions and errors into responses.
     *
     * @var Closure(Throwable, ServerRequest): Response
     *
     * @internal
     */
    private readonly Closure $errorHandler;

    /**
     * Converts an exception or error into a generic PSR-7 HTTP response.
     *
     * This is the default error handler. The response does not reveal any
     * details about the error, those are written to PHP's error log instead.
     * The exception code is used as status code if it is a valid HTTP error
     * status (400 to 599), otherwise the status is "500 Internal Server Error".
     *
     * @param Throwable $error The exception or error to convert
     *
     * @return Response A PSR-7 compatible HTTP response
     *
     * @internal
     */
    private function convert(Throwable $error): Response
    {
        error_log(
            sprintf(
                'Uncaught %s: %s in %s on line %d',
                get_debug_type($error),
                $error->getMessage(),
                $error->getFile(),
                $error->getLine()
            )
        );
        $response = new GuzzleResponse(
            filter_var(
                $error->getCode(),
                \FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'default' => 500,
                        'min_range' => 400,
                        'max_range' => 599
                    ]
                ]
            ),
            ['Content-Type' => 'text/plain; charset=utf-8']
        );
        $response->getBody()->write(
            sprintf('%d %s', $response->getStatusCode(), $response->getReasonPhrase())
        );
        return $response;
    }

    /**
     * Process an incoming server request and convert any exception or error
     * thrown while handling it into a response.
     *
     * @param ServerRequest $request The server request to process
     * @param RequestHandler $handler The request handler to delegate to
     *
     * @return Response The response object
     *
     * @api
     */
    #[Override]
    public function process(ServerRequest $request, RequestHandler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $error) {
            return ($this->errorHandler)($error, $request);
        }
    }

    /**
     * Create a middleware converting exceptions and errors into responses.
     *
     * @param ?callable(Throwable, ServerRequest): Response $errorHandler Callback converting exceptions and errors
     *        into a response (defaults to a generic error response)
     */
    public function __construct(?callable $errorHandler = null)
    {
        $this->errorHandler = $errorHandler !== null ? Closure::fromCallable($errorHandler) : $this->convert(...);
    }
}
