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

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use OCC\PSR15\Defaults;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests the defaults with Guzzle installed. Without Guzzle, a LogicException
 * is thrown instead, which can't be tested here, since Guzzle is a dev
 * dependency.
 */
#[CoversClass(Defaults::class)]
final class DefaultsTest extends TestCase
{
    #[Test]
    public function itProvidesGuzzlesResponseFactory(): void
    {
        $this->assertInstanceOf(HttpFactory::class, Defaults::responseFactory(self::class));
    }

    #[Test]
    #[BackupGlobals(true)]
    public function itCreatesTheServerRequestFromGlobals(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/from-globals';

        $request = Defaults::serverRequest(self::class);

        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/from-globals', $request->getUri()->getPath());
    }
}
