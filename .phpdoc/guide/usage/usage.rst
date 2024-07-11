.. title:: Usage

Usage
#####

.. sidebar:: Table of Contents
  .. contents::

The following example shows a very basic *Queue-based HTTP Server Request Handler* using just two simple middlewares
(called `MiddlewareOne` and `MiddlewareTwo`).

Middlewares
===========

First of all, we need some middlewares to process our server request. Although we could use any middleware implementing
the `Psr/Http/Server/MiddlewareInterface` (e.g. from `this great collection <https://github.com/middlewares>`_), we
will write our own using the :doc:`../overview/abstractmiddleware` provided by this package.

The abstract middleware already implements a complete middleware, but it will just pass requests through without doing
anything. In order to have it do something, we need to implement our own :php:method:`OCC\PSR15\AbstractMiddleware::processRequest()`
or :php:method:`OCC\PSR15\AbstractMiddleware::processResponse()` method, or both of them.

The logic here is the same as with every `PSR-15: HTTP Server Request Handler <https://www.php-fig.org/psr/psr-15/>`_
middleware: The request gets passed through all middlewares' `processRequest()` methods in order of their addition to
the queue, then a response is created and passed through all `processResponse()` methods, **but in reverse order**! So
the first middleware in the queue gets the request first, but the response last.

Our first middleware is very simple and just adds an attribute to the server request.

.. code-block:: php
  use OCC\PSR15\AbstractMiddleware;
  use Psr\Http\Message\ServerRequestInterface as ServerRequest;

  class MiddlewareOne extends AbstractMiddleware
  {
      /**
       * Process an incoming server request before delegating to next middleware.
       *
       * @param ServerRequest $request The incoming server request
       *
       * @return ServerRequest The processed server request
       */
      protected function processRequest(ServerRequest $request): ServerRequest
      {
          // Let's just add a new attribute to the request to later check
          // which middleware was passed last.
          return $request->withAttribute('LastMiddlewarePassed', 'MiddlewareOne');
      }
  }

For a queue to make sense we need at least a second middleware, so let's create another one. Again, we will add an
attribute to the request, but with the same name. So, whichever middleware handles the request last overwrites the
attribute with its value. This way we can later check if our middlewares were passed in the correct order.

.. code-block:: php
  use OCC\PSR15\AbstractMiddleware;
  use Psr\Http\Message\ServerRequestInterface as ServerRequest;

  class MiddlewareTwo extends AbstractMiddleware
  {
      /**
       * Process an incoming server request before delegating to next middleware.
       *
       * @param ServerRequest $request The incoming server request
       *
       * @return ServerRequest The processed server request
       */
      protected function processRequest(ServerRequest $request): ServerRequest
      {
          // We add the same request attribute as in MiddlewareOne, effectively
          // overwriting its value.
          return $request->withAttribute('LastMiddlewarePassed', 'MiddlewareTwo');
      }
  }

Also, we want to set the status code of the response according to the final value of our request attribute. Therefore,
we need to implement `processResponse()` as well. We can do that in either one of our middlewares because it's the only
response manipulation in our example, so the order of processing doesn't make a difference (remember: `MiddlewareTwo`
gets to handle the response before `MiddlewareOne`). Let's go with `MiddlewareTwo`.

.. code-block:: php
  use OCC\PSR15\AbstractMiddleware;
  use Psr\Http\Message\ResponseInterface as Response;
  use Psr\Http\Message\ServerRequestInterface as ServerRequest;

  class MiddlewareTwo extends AbstractMiddleware
  {
      // MiddlewareTwo::processRequest() remains unchanged (see above).

      /**
       * Process an incoming response before returning it to previous middleware.
       *
       * @param Response $response The incoming response
       *
       * @return Response The processed response
       */
      protected function processResponse(Response $response): Response
      {
          // First we need to get the request attribute.
          $lastMiddlewarePassed = $this->requestHandler->request->getAttribute('LastMiddlewarePassed');
          if ($lastMiddlewarePassed === 'MiddlewareTwo') {
              // Great, MiddlewareTwo was passed after MiddlewareOne,
              // let's return status code 200!
              return $response->withStatus(200);
          } else {
              // Oh no, something went wrong! We'll send status code 500.
              return $response->withStatus(500);
          }
      }
  }

Well done! We now have two middlewares.

Request Handler
===============

Let's use a :doc:`../overview/queuerequesthandler` to pass a server request through both of our middlewares in the
correct order.

.. code-block:: php
  use OCC\PSR15\QueueRequestHandler;

  // First of all, we instantiate the request handler.
  // At this point we could already provide an array of middlewares as argument and
  // skip the next step, but then we wouldn't learn how to use the MiddlewareQueue.
  $requestHandler = new QueueRequestHandler();

  // We can access the MiddlewareQueue as a property of the request handler.
  // Let's add both of our middlewares, MiddlewareOne and MiddlewareTwo. Since
  // this is a FIFO queue, the order is very important!
  $requestHandler->queue->enqueue(new MiddlewareOne());
  $requestHandler->queue->enqueue(new MiddlewareTwo());

  // And we are ready to handle incoming requests!
  // We don't even need to pass the server request to this method, because
  // the constructor already took care of that!
  $finalResponse = $requestHandler->handle();

  // Now we can pass the final response back to our application.
  // Alternatively, we can also return it directly to the client.
  $requestHandler->respond();

  // If we did everything right, the client should now receive an HTTP response
  // with status code 200 (OK).

And that's it!

Diving Deeper
=============

To familiarize yourself with the FIFO principle of the middleware queue, you can try to exchange the two lines adding
the middlewares to the queue, i.e. adding `MiddlewareTwo` first and `MiddlewareOne` second. This will result in an HTTP
response with status code `500 (Internal Server Error)`.

This is exactly what we intended: Have a look at `MiddlewareTwo::processResponse()` again! If `$lastMiddlewarePassed`
is not `MiddlewareTwo` (which it isn't when `MiddlewareOne` is added to the queue after `MiddlewareTwo`), we set the
response status code to `500`.
