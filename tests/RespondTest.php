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

use OCC\PSR15\Defaults;
use OCC\PSR15\MiddlewareQueue;
use OCC\PSR15\QueueRequestHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests QueueRequestHandler::respond() with PHP's built-in web server, since
 * headers are not sent on the command line.
 */
#[CoversClass(QueueRequestHandler::class)]
#[UsesClass(Defaults::class)]
#[UsesClass(MiddlewareQueue::class)]
final class RespondTest extends TestCase
{
    /** @var resource|null */
    private static $server = null;

    private static string $address = '';

    public static function setUpBeforeClass(): void
    {
        // Find a free port by letting the operating system choose one.
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        if ($socket === false) {
            self::markTestSkipped('Could not find a free port for the built-in web server.');
        }
        self::$address = (string) stream_socket_get_name($socket, false);
        fclose($socket);

        $server = proc_open(
            [PHP_BINARY, '-d', 'output_buffering=0', '-S', self::$address, '-t', __DIR__ . '/Fixtures/server'],
            [['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']],
            $pipes
        );
        if ($server === false) {
            self::markTestSkipped('Could not start the built-in web server.');
        }
        self::$server = $server;

        // Wait up to five seconds for the server to accept connections.
        for ($i = 0; $i < 50; ++$i) {
            $connection = @fsockopen('tcp://' . self::$address);
            if ($connection !== false) {
                fclose($connection);
                return;
            }
            usleep(100000);
        }
        self::markTestSkipped('The built-in web server did not start in time.');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$server !== null) {
            proc_terminate(self::$server);
            proc_close(self::$server);
            self::$server = null;
        }
    }

    /**
     * Request the front controller and return the status code, headers and body.
     *
     * @param array<string, string|int> $query The query parameters defining the response
     *
     * @return array{int, list<string>, string}
     */
    private function request(array $query): array
    {
        $context = stream_context_create([
            'http' => ['ignore_errors' => true, 'follow_location' => 0]
        ]);
        $stream = fopen('http://' . self::$address . '/?' . http_build_query($query), 'r', false, $context);
        $this->assertIsResource($stream, 'Request to the built-in web server failed.');
        /** @var list<string> $headers */
        $headers = stream_get_meta_data($stream)['wrapper_data'];
        $body = (string) stream_get_contents($stream);
        fclose($stream);
        $status = (int) explode(' ', $headers[0])[1];
        return [$status, array_slice($headers, 1), $body];
    }

    /**
     * @param list<string> $headers
     *
     * @return list<string> The values of all header lines with the given name
     */
    private static function headerValues(array $headers, string $name): array
    {
        $values = [];
        foreach ($headers as $line) {
            [$lineName, $value] = explode(':', $line, 2) + [1 => ''];
            if (strcasecmp($lineName, $name) === 0) {
                $values[] = trim($value);
            }
        }
        return $values;
    }

    #[Test]
    public function itSendsStatusHeadersAndBody(): void
    {
        [$status, , $body] = $this->request(['status' => 201, 'body' => 'Created']);

        $this->assertSame(201, $status);
        $this->assertSame('Created', $body);
    }

    #[Test]
    public function itSendsEveryHeaderValueOnASeparateLine(): void
    {
        [, $headers] = $this->request(['cookies' => 2]);

        $this->assertSame(
            [
                'php=set-by-php',
                'cookie1=value1; Expires=Wed, 21 Oct 2026 07:28:00 GMT',
                'cookie2=value2; Expires=Wed, 21 Oct 2026 07:28:00 GMT'
            ],
            self::headerValues($headers, 'Set-Cookie')
        );
        $this->assertSame(['Accept', 'Origin'], self::headerValues($headers, 'Vary'));
    }

    #[Test]
    public function itReplacesHeadersAlreadySetByPhp(): void
    {
        [, $headers] = $this->request([]);

        $this->assertSame(['no-store'], self::headerValues($headers, 'Cache-Control'));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function statusProvider(): array
    {
        return [
            '200 with Location' => [200, 'Location: /elsewhere'],
            '202 with Location' => [202, 'Location: /status'],
            '404 with Location' => [404, 'Location: /elsewhere'],
            '500 with Location' => [500, 'Location: /elsewhere'],
            '307 with Location' => [307, 'Location: /elsewhere'],
            '200 with WWW-Authenticate' => [200, 'WWW-Authenticate: Basic'],
            '403 with WWW-Authenticate' => [403, 'WWW-Authenticate: Basic']
        ];
    }

    #[Test]
    #[DataProvider('statusProvider')]
    public function phpDoesNotChangeTheStatusCode(int $expected, string $header): void
    {
        [$status] = $this->request(['status' => $expected, 'header' => $header]);

        $this->assertSame($expected, $status);
    }

    #[Test]
    public function itRefusesToRespondAfterOutputWasSent(): void
    {
        [, , $body] = $this->request(['early' => 1]);

        $this->assertStringStartsWith('Early output / RuntimeException: Headers already sent in ', $body);
    }
}
