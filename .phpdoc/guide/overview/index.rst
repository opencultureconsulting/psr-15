.. title:: Overview

Overview
########

The package contains an implementation of `PSR-15: HTTP Server Request Handlers <https://www.php-fig.org/psr/psr-15/>`_
in a queue-based variant. A :doc:`queuerequesthandler` handles an incoming HTTP request by passing it through a queue
of one or more middlewares. The :doc:`middlewarequeue` provides the middlewares in first-in, first-out (FIFO) order,
i.e. the HTTP request is passed from middleware to middleware preserving the order in which the middlewares were added
to the queue. An :doc:`abstractmiddleware` helps developing your own middlewares, but you can also use any middleware
implementing the `Psr\Http\Server\MiddlewareInterface <https://packagist.org/packages/psr/http-server-middleware>`_.

All files share the highest coding standards of `PHPStan <https://phpstan.org/>`_ and `Psalm <https://psalm.dev/>`_,
and full `PSR-12 <https://www.php-fig.org/psr/psr-12/>`_ compliance to make sure they can be combined and easily used
in other projects.

.. toctree::
  :maxdepth: 2

  queuerequesthandler
  middlewarequeue
  abstractmiddleware
