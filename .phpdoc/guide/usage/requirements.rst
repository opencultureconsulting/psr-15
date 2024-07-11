.. title:: Requirements

Requirements
############

Environment
===========

This package requires at least **PHP 8.1**.

It is highly recommended to use `Composer <https://getcomposer.org/>`_ for dependency management and autoloading,
although it is technically not strictly required for using any of these classes. But it certainly makes it a lot
easier!

Dependencies
============

This package obviously depends on `psr/http-server-handler <https://packagist.org/packages/psr/http-server-handler>`_
and `psr/http-server-middleware <https://packagist.org/packages/psr/http-server-middleware>`_ which define the standard
`PSR-15: HTTP Server Request Handlers <https://www.php-fig.org/psr/psr-15/>`_ interfaces.

It uses the `PSR-7: HTTP Message <https://www.php-fig.org/psr/psr-7/>`_ implementations for server request and response
of the great `guzzlehttp/psr7 <https://packagist.org/packages/guzzlehttp/psr7>`_ library.

The middleware queue is based on a `StrictQueue <https://opencultureconsulting.github.io/php-basics/guides/overview/datastructures.html#strictqueue>`_
of the `opencultureconsulting/basics <https://packagist.org/packages/opencultureconsulting/basics>`_ package which also
provides some useful traits.
