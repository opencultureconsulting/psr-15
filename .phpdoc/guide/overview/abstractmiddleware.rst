.. title:: AbstractMiddleware

AbstractMiddleware
##################

.. sidebar:: Table of Contents
  .. contents::

The `AbstractMiddleware` is a little helper for creating your own middlewares. It processes the incoming request before
handing it over to the next middleware in line, and later processes the response before returning it to the previous
middleware. Originally both methods just return their argument unchanged, so you should implement either one of them or
both as needed.

The `AbstractMiddleware` implements the
`Psr\Http\Server\MiddlewareInterface <https://www.php-fig.org/psr/psr-15/#22-psrhttpservermiddlewareinterface>`_
following PHP-FIG's recommendation `PSR-15: HTTP Server Request Handlers <https://www.php-fig.org/psr/psr-15/>`_.

Properties
==========

The `AbstractMiddleware` has a single protected property `AbstractMiddleware::requestHandler` referencing the request
handler which called the middleware. This can be used to access the request and/or response object (as properties of
:doc:`queuerequesthandler`) when they are otherwise not available.

Methods
=======

The `AbstractMiddleware` provides one public API method and two protected methods. While the former is final and makes
sure it implements the `Psr\Http\Server\MiddlewareInterface`, the latter are intended to be extended in your own class.

The main :php:method:`OCC\PSR15\AbstractMiddleware::process()` method implements the interface and is also called when
invoking the middleware object directly. It first passes the incoming request to the
:php:method:`OCC\PSR15\AbstractMiddleware::processRequest()` method, then hands over the result to the request handler
to receive a response, which is then processed by :php:method:`OCC\PSR15\AbstractMiddleware::processResponse()` before
returning it back to the request handler again.

Processing a Request
--------------------

The default method of `AbstractMiddleware` just returns the request unchanged. If you need to process the request, you
have to implement your own `processRequest()` method. It takes a request object as only argument and must return a
valid request object as well. Just make sure it follows PHP-FIG's standard recommendation
`PSR-7: HTTP Message Interfaces <https://www.php-fig.org/psr/psr-7/>`_ and implements the
`Psr\Http\Message\ServerRequestInterface <https://www.php-fig.org/psr/psr-7/#321-psrhttpmessageserverrequestinterface>`_.

Processing a Response
---------------------

The default method of `AbstractMiddleware` just returns the response unchanged. If you need to process the response,
you have to implement your own `processResponse()` method. It takes a response object as only argument and must return
a valid response object as well. Just make sure it follows PHP-FIG's standard recommendation
`PSR-7: HTTP Message Interfaces <https://www.php-fig.org/psr/psr-7/>`_ and implements the
`Psr\Http\Message\ResponseInterface <https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface>`_.
