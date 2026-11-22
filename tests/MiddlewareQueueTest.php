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

namespace OCC\PSR15\Tests;

use OCC\Basics\DataStructures\Exceptions\InvalidDataTypeException;
use OCC\PSR15\MiddlewareQueue;
use OCC\PSR15\Tests\Fixtures\TrailMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(MiddlewareQueue::class)]
final class MiddlewareQueueTest extends TestCase
{
    #[Test]
    public function itIsEmptyByDefault(): void
    {
        $this->assertCount(0, new MiddlewareQueue());
    }

    #[Test]
    public function itReturnsMiddlewaresInFifoOrder(): void
    {
        $first = new TrailMiddleware('A');
        $second = new TrailMiddleware('B');
        $third = new TrailMiddleware('C');
        $queue = new MiddlewareQueue([$first, $second]);
        $queue->enqueue($third);

        $this->assertSame($first, $queue->dequeue());
        $this->assertSame($second, $queue->dequeue());
        $this->assertSame($third, $queue->dequeue());
    }

    #[Test]
    public function itRejectsObjectsNotImplementingMiddlewareInterface(): void
    {
        $this->expectException(InvalidDataTypeException::class);
        (new MiddlewareQueue())->enqueue(new stdClass());
    }

    #[Test]
    public function instancesAreIndependent(): void
    {
        $first = new MiddlewareQueue([new TrailMiddleware('A')]);
        $second = new MiddlewareQueue([new TrailMiddleware('B'), new TrailMiddleware('C')]);

        $this->assertNotSame($first, $second);
        $this->assertCount(1, $first);
        $this->assertCount(2, $second);
    }
}
