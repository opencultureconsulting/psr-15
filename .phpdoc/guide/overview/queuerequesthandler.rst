.. title:: QueueRequestHandler

QueueRequestHandler
###################

.. sidebar:: Table of Contents
  .. contents::

The `QueueRequestHandler` is the core piece of this package. It fetches incoming HTTP requests, passes them through a
queue of middlewares and finally sends the response back to the client. It also catches any exceptions not handled by
a middleware and turns them into a proper HTTP error response.

The `QueueRequestHandler` implements the
`Psr\Http\Server\RequestHandlerInterface <https://www.php-fig.org/psr/psr-15/#21-psrhttpserverrequesthandlerinterface>`_
following PHP-FIG's recommendation `PSR-15: HTTP Server Request Handlers <https://www.php-fig.org/psr/psr-15/>`_.

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
`Psr\Http\Message\ServerRequestInterface <https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface>`_.

When instantiating a `QueueRequestHandler` the `$request` property is initially set by fetching the actual server
request data from superglobals. The property is reset each time the request is passed through a middleware and thus
always represents the current state of the request.

HTTP Response
-------------

The response can be read as `QueueRequestHandler::response`. It also follows PHP-FIG's standard recommendation
`PSR-7: HTTP Message Interfaces <https://www.php-fig.org/psr/psr-7/>`_ and implements the
`Psr\Http\Message\ResponseInterface <https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface>`_.

When instantiating a `QueueRequestHandler` the `$response` property is initially set as a blank HTTP response with
status code `200`. The property is reset each time the response is passed through a middleware and thus
always represents the latest state of the response.

Both, request and response, use the awesome implementations of `Guzzle <https://github.com/guzzle/psr7>`_.

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

In case of an error the request handler catches any exception and creates a response with the exception code as status
code (if it's within the valid range of HTTP status codes, otherwise it's set to `500 (Internal Server Error)`), and
the exception message as body.

Sending the Response
--------------------

Sending the final response to the client is as easy as calling :php:method:`OCC\PSR15\QueueRequestHandler::respond()`.
Optionally, you can provide an exit code as argument (an integer in the range `0` to `254`). If you do so, script
execution is stopped after sending out the response and the given exit status is set. The status `0` means the request
was handled successfully, every other status is considered an error.
