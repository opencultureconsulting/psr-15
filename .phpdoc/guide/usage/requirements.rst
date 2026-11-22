.. title:: Requirements

Requirements
############

Environment
===========

This package requires at least **PHP 8.2**.

It is highly recommended to use `Composer <https://getcomposer.org/>`_ for dependency management and autoloading,
although it is technically not strictly required for using any of these classes. But it certainly makes it a lot
easier!

Dependencies
============

This package obviously depends on `psr/http-server-handler <https://packagist.org/packages/psr/http-server-handler>`_
and `psr/http-server-middleware <https://packagist.org/packages/psr/http-server-middleware>`_ which define the standard
`PSR-15: HTTP Server Request Handlers <https://www.php-fig.org/psr/psr-15/>`_ interfaces.

It works with any implementation of `PSR-7: HTTP Message Interfaces <https://www.php-fig.org/psr/psr-7/>`_ and
`PSR-17: HTTP Factories <https://www.php-fig.org/psr/psr-17/>`_. If the great
`guzzlehttp/psr7 <https://packagist.org/packages/guzzlehttp/psr7>`_ library is installed, it's used by default, so you
don't have to configure anything. Otherwise, you have to pass the server request and a response factory of your
preferred implementation to the constructors (see :doc:`../overview/queuerequesthandler` and
:doc:`../overview/errormiddleware`). To use Guzzle, just install it alongside this package:

.. code-block:: shell
  composer require guzzlehttp/psr7

The middleware queue is based on a `StrictQueue <https://code.opencultureconsulting.com/php-basics/guides/overview/datastructures.html#strictqueue>`_
of the `opencultureconsulting/basics <https://packagist.org/packages/opencultureconsulting/basics>`_ package which also
provides some useful traits.
