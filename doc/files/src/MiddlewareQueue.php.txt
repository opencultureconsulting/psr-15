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

use OCC\Basics\DataStructures\StrictQueue;
use OCC\Basics\Traits\Singleton;
use Psr\Http\Server\MiddlewareInterface as Middleware;

/**
 * Queue of PSR-15 Middlewares to process HTTP Server Requests.
 *
 * @author Sebastian Meyer <sebastian.meyer@opencultureconsulting.com>
 * @package PSR15
 *
 * @method static static getInstance(iterable<\Psr\Http\Server\MiddlewareInterface> $middlewares)
 *
 * @extends StrictQueue<Middleware>
 */
final class MiddlewareQueue extends StrictQueue
{
    use Singleton;

    /**
     * Create a queue of PSR-15 compatible middlewares.
     *
     * @param iterable<array-key, Middleware> $middlewares Initial set of PSR-15 middlewares
     */
    private function __construct(iterable $middlewares = [])
    {
        parent::__construct([Middleware::class]);
        $this->append(...$middlewares);
    }
}
