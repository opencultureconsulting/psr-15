.. title:: QueueRequestHandler

QueueRequestHandler
###################

.. sidebar:: Table of Contents
  .. contents::

The `QueueRequestHandler` is the core piece of this package. It fetches incoming HTTP requests, passes them through a
queue of middlewares and finally sends the response back to the client.

The `QueueRequestHandler` implements the `Psr\Http\Server\RequestHandlerInterface` following PHP-FIG's recommendation
`PSR-15: HTTP Server Request Handlers <https://www.php-fig.org/psr/psr-15/>`_.

For a minimal working example have a look at :doc:`../usage/usage`.

Properties
==========

The `QueueRequestHandler` has three **read-only** properties. They are initially set at instantiation and can be
directly accessed from the object via magic methods (e.g. `$requestHandler->queue`).

Middleware Queue
----------------

The queue of middlewares can be accessed as `QueueRequestHandler::queue` and offers a handy API to `enqueue()`,
`dequeue()` or otherwise manipulate its contents. All middlewares must implement `Psr\Http\Server\MiddlewareInterface`.
Have a look at :doc:`middlewarequeue` for more details.

When instantiating a `QueueRequestHandler` the queue defaults to being empty. But you can optionally pass an iterable
set of middlewares to the constructor which are then put into the queue. To demonstrate, the following examples both
have exactly the same result.

  Examples:

  .. code-block:: php
    use OCC\PSR15\QueueRequestHandler;

    $middlewares = [
        new MiddlewareOne(),
        new MiddlewareTwo()
    ];

    $requestHandler = new QueueRequestHandler($middlewares);

  .. code-block:: php
    use OCC\PSR15\QueueRequestHandler;

    $requestHandler = new QueueRequestHandler();

    $requestHandler->queue->enqueue(new MiddlewareOne());
    $requestHandler->queue->enqueue(new MiddlewareTwo());

HTTP Server Request
-------------------

The server request is always available as `QueueRequestHandler::request`. It follows PHP-FIG's standard recommendation
`PSR-7: HTTP Message Interfaces <https://www.php-fig.org/psr/psr-7/>`_ and implements the
`Psr\Http\Message\ServerRequestInterface`.

When instantiating a `QueueRequestHandler` you can pass a server request as second argument to the constructor. It's
used whenever `handle()` is called without a request. If you don't pass one, the request is created from PHP's
superglobals using `Guzzle <https://github.com/guzzle/psr7>`_, so in that case `guzzlehttp/psr7` must be installed. The
property is reset each time the request is passed through a middleware and thus always represents the current state of
the request.

HTTP Response
-------------

The response can be read as `QueueRequestHandler::response`. It also follows PHP-FIG's standard recommendation
`PSR-7: HTTP Message Interfaces <https://www.php-fig.org/psr/psr-7/>`_ and implements the
`Psr\Http\Message\ResponseInterface`.

For each request the `$response` property is initially set as a blank HTTP response with status code `200`. It's
created by a `PSR-17: HTTP Factories <https://www.php-fig.org/psr/psr-17/>`_ response factory, which you can pass as
third argument to the constructor. If you don't pass one, Guzzle's factory is used. The property is reset each time the
response is passed through a middleware and thus always represents the latest state of the response.

  Example:

  .. code-block:: php
    use Nyholm\Psr7\Factory\Psr17Factory;
    use Nyholm\Psr7Server\ServerRequestCreator;
    use OCC\PSR15\QueueRequestHandler;

    // Use the PSR-7 implementation of nyholm/psr7 instead of Guzzle.
    $factory = new Psr17Factory();
    $request = (new ServerRequestCreator($factory, $factory, $factory, $factory))->fromGlobals();

    $requestHandler = new QueueRequestHandler($middlewares, $request, $factory);

Methods
=======

The `QueueRequestHandler` provides two public API methods, :php:method:`OCC\PSR15\QueueRequestHandler::handle()` and
:php:method:`OCC\PSR15\QueueRequestHandler::respond()`. As their names suggest, the former handles the server request
while the latter sends the response back to the client. Invoking the request handler object directly does the same as
calling the `handle()` method.

Handling a Server Request
-------------------------

After adding at least one middleware to the queue, you can start handling a request by simply calling
:php:method:`OCC\PSR15\QueueRequestHandler::handle()`. Optionally, you can pass a request object as argument, but since
the actual server request was already fetched in the constructor and will be used by default, most of the time you
don't need to. All request objects must implement `Psr\Http\Message\ServerRequestInterface`.

The `handle()` method returns the final response after passing it through all middlewares. The response object always
implements `Psr\Http\Message\ResponseInterface`.

Every call of `handle()` works on its own copy of the queue, so you can call it again with another request and it will
run through the same middlewares from the beginning. This makes it possible to reuse one request handler for many
requests, e.g. in long-running PHP servers. While a request is being handled, `QueueRequestHandler::queue` returns the
copy for that request. So if a middleware adds or removes other middlewares, this only applies to the current request
and not to subsequent ones.

Handling Errors
---------------

As intended by `PSR-15 <https://www.php-fig.org/psr/psr-15/>`_, the request handler doesn't catch exceptions thrown by a
middleware. They are passed through all previous middlewares, so each of them can handle errors as needed, and finally
to the caller of `handle()`. To always get a response, add the :doc:`errormiddleware` as the first middleware to the
queue. It converts any exception or error into an HTTP error response.

Sending the Response
--------------------

Sending the final response to the client is as easy as calling :php:method:`OCC\PSR15\QueueRequestHandler::respond()`.
