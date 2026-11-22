.. title:: ErrorMiddleware

ErrorMiddleware
###############

.. sidebar:: Table of Contents
  .. contents::

The `ErrorMiddleware` catches any exception or error thrown while processing a server request and converts it into an
HTTP error response, so a response is always generated.

`PSR-15: HTTP Server Request Handlers <https://www.php-fig.org/psr/psr-15/>`_ lets exceptions pass through all
middlewares, so each of them can handle errors as needed. The :doc:`queuerequesthandler` follows this and doesn't catch
exceptions itself. Instead, as recommended by PSR-15, the `ErrorMiddleware` should be the first middleware in the queue
to wrap all further processing.

The `ErrorMiddleware` implements the `Psr\Http\Server\MiddlewareInterface`.

  Example:

  .. code-block:: php
    use OCC\PSR15\ErrorMiddleware;
    use OCC\PSR15\QueueRequestHandler;

    $requestHandler = new QueueRequestHandler([
        new ErrorMiddleware(),
        new MiddlewareOne(),
        new MiddlewareTwo()
    ]);

Middlewares added to the queue before the `ErrorMiddleware` receive its error response like any other response.
Middlewares added after it can still catch exceptions themselves, e.g. to recover from a specific error, by wrapping
their call of `$handler->handle()` in a `try`/`catch` block. Keep in mind that middlewares skipped by an exception don't
get to process the response, e.g. the :php:method:`OCC\PSR15\AbstractMiddleware::processResponse()` method of a
middleware isn't called if an exception was thrown further down the queue.

Default Error Response
======================

By default, the `ErrorMiddleware` returns a plain text response which only states the HTTP status, e.g.
`500 Internal Server Error`. The exception code is used as status code if it's a valid HTTP error status (`400` to
`599`), otherwise it's set to `500`.

The response doesn't reveal any details about the error to the client. Those are written to PHP's error log instead
(see `error_log() <https://www.php.net/manual/en/function.error-log.php>`_), including the class of the exception, its
message, and the file and line where it was thrown.

The response is created by a `PSR-17: HTTP Factories <https://www.php-fig.org/psr/psr-17/>`_ response factory, which
you can pass as second argument to the constructor, e.g. `new ErrorMiddleware(null, $responseFactory)`. If you don't
pass one, Guzzle's factory is used, so in that case `guzzlehttp/psr7` must be installed.

Custom Error Handler
====================

If you want to create the error response yourself, e.g. to render a nice error page or to show details while
developing, you can pass an error handler to the constructor. It can be any callable which receives the exception or
error and the server request, and returns a response.

  Example:

  .. code-block:: php
    use GuzzleHttp\Psr7\Response;
    use OCC\PSR15\ErrorMiddleware;
    use Psr\Http\Message\ServerRequestInterface as ServerRequest;

    $errorHandler = function (Throwable $error, ServerRequest $request): Response
    {
        return new Response(
            500,
            ['Content-Type' => 'text/plain; charset=utf-8'],
            sprintf('%s: %s', $error::class, $error->getMessage())
        );
    };

    $errorMiddleware = new ErrorMiddleware($errorHandler);

If the error handler itself throws an exception, it isn't caught by the `ErrorMiddleware`.
