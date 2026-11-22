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

use GuzzleHttp\Psr7\HttpFactory as GuzzleFactory;
use GuzzleHttp\Psr7\ServerRequest as GuzzleRequest;
use LogicException;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;

use function class_exists;
use function sprintf;

/**
 * Provides default PSR-7 objects if no other implementation is given.
 *
 * Guzzle's PSR-7 implementation is used as default if it is installed. If it
 * isn't, the PSR-7 objects must be passed explicitly.
 *
 * @author Sebastian Meyer <sebastian.meyer@opencultureconsulting.com>
 * @package PSR15
 *
 * @internal
 */
final class Defaults
{
    /**
     * Get a PSR-17 response factory.
     *
     * @param string $class The class requiring the response factory
     *
     * @return ResponseFactory The default response factory
     *
     * @throws LogicException if Guzzle is not installed
     */
    public static function responseFactory(string $class): ResponseFactory
    {
        self::requireGuzzle(GuzzleFactory::class, $class, 'a PSR-17 response factory');
        return new GuzzleFactory();
    }

    /**
     * Get the PSR-7 server request created from PHP's superglobals.
     *
     * @param string $class The class requiring the server request
     *
     * @return ServerRequest The default server request
     *
     * @throws LogicException if Guzzle is not installed
     */
    public static function serverRequest(string $class): ServerRequest
    {
        self::requireGuzzle(GuzzleRequest::class, $class, 'a PSR-7 server request');
        return GuzzleRequest::fromGlobals();
    }

    /**
     * Make sure the required Guzzle class is available.
     *
     * @param string $guzzleClass The required Guzzle class
     * @param string $class The class requiring it
     * @param string $argument The description of the missing constructor argument
     *
     * @return void
     *
     * @throws LogicException if the Guzzle class is not available
     */
    private static function requireGuzzle(string $guzzleClass, string $class, string $argument): void
    {
        if (!class_exists($guzzleClass)) {
            throw new LogicException(
                sprintf(
                    'Pass %s to the constructor of %s or install guzzlehttp/psr7 to use it as default.',
                    $argument,
                    $class
                )
            );
        }
    }

    /**
     * This class only provides static methods.
     */
    private function __construct()
    {
    }
}
