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

/**
 * Front controller for the built-in web server used by RespondTest.
 *
 * The query parameters define the response: "status", "header" (a single
 * header as "Name: value"), "cookies" (number of cookies to set), "body"
 * and "early" (send output before responding).
 */

declare(strict_types=1);

use GuzzleHttp\Psr7\ServerRequest;
use OCC\PSR15\QueueRequestHandler;
use OCC\PSR15\Tests\Fixtures\CallbackMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$params = $_GET;

// Headers set by PHP itself before responding, e.g. by session_start().
header('Cache-Control: private');
setcookie('php', 'set-by-php');

$requestHandler = new QueueRequestHandler(
    [
        new CallbackMiddleware(
            static function (
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ) use ($params): ResponseInterface {
                $response = $handler->handle($request)
                    ->withStatus((int) ($params['status'] ?? 200))
                    ->withHeader('Cache-Control', 'no-store')
                    ->withAddedHeader('Vary', 'Accept')
                    ->withAddedHeader('Vary', 'Origin');
                for ($i = 1; $i <= (int) ($params['cookies'] ?? 0); ++$i) {
                    $cookie = "cookie$i=value$i; Expires=Wed, 21 Oct 2026 07:28:00 GMT";
                    $response = $response->withAddedHeader('Set-Cookie', $cookie);
                }
                if (isset($params['header'])) {
                    [$name, $value] = explode(':', (string) $params['header'], 2);
                    $response = $response->withHeader($name, trim($value));
                }
                $response->getBody()->write((string) ($params['body'] ?? ''));
                return $response;
            }
        )
    ],
    ServerRequest::fromGlobals()
);
$requestHandler->handle();

if (isset($params['early'])) {
    echo 'Early output';
    try {
        $requestHandler->respond();
    } catch (RuntimeException $exception) {
        echo ' / ', get_class($exception), ': ', $exception->getMessage();
    }
    exit;
}

$requestHandler->respond();
