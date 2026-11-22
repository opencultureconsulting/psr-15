.. title:: Changelog

Changelog
#########

.. sidebar:: Table of Contents
  .. contents::

Unreleased
==========

**Breaking Changes:**

* :php:class:`OCC\PSR15\MiddlewareQueue` is no longer a singleton, so every
  :php:class:`OCC\PSR15\QueueRequestHandler` now gets its own queue of middlewares. Previously all handlers shared one
  queue and middlewares passed to any but the first handler were silently ignored. Use `new MiddlewareQueue()` instead of
  `MiddlewareQueue::getInstance()`.
* :php:method:`OCC\PSR15\QueueRequestHandler::handle()` now works on a copy of the middleware queue for each request,
  so one handler can process any number of requests (e.g. in long-running servers like RoadRunner, Swoole or
  FrankenPHP). Previously every call after the first returned the stale response of the first run without invoking any
  middleware. While a request is being handled, `QueueRequestHandler::queue` returns that request's copy, so changes
  made by middlewares only apply to the current request.
* :php:class:`OCC\PSR15\QueueRequestHandler` no longer catches exceptions thrown by a middleware. As intended by
  PSR-15, they are now passed through all previous middlewares, so these can handle them as needed, and finally to the
  caller of `handle()`. Add the new :php:class:`OCC\PSR15\ErrorMiddleware` as the first middleware to convert them
  into responses. Error responses no longer reveal any details to the client and the `Warning` header was removed (see
  below).
* :php:class:`OCC\PSR15\AbstractMiddleware` now works with any request handler implementing
  `Psr\Http\Server\RequestHandlerInterface` instead of throwing a `DomainException` for anything but a
  :php:class:`OCC\PSR15\QueueRequestHandler`. Therefore the type of the protected property
  `AbstractMiddleware::requestHandler` changed to `RequestHandlerInterface`. Middlewares accessing properties of
  `QueueRequestHandler` (e.g. `$this->requestHandler->request`) still work with it, but static analyzers will report
  them unless you check the type with `instanceof QueueRequestHandler` first.
* `guzzlehttp/psr7` is no longer a required dependency. The package now works with any PSR-7 and PSR-17
  implementation. Guzzle is still used by default if it's installed, so add it to your own dependencies if you rely on
  it (`composer require guzzlehttp/psr7`). Otherwise, pass the server request and a response factory to the
  constructors of :php:class:`OCC\PSR15\QueueRequestHandler` and :php:class:`OCC\PSR15\ErrorMiddleware`.

**Minor Changes:**

* Added :php:class:`OCC\PSR15\ErrorMiddleware` to convert any exception or error (i.e. every `Throwable`) into an
  HTTP error response. By default, the response only states the HTTP status and the details are written to PHP's error
  log. The exception code is used as status if it's a valid HTTP error status (`400` to `599`), otherwise it's `500`.
  A custom error handler can be passed to the constructor.
* Added optional server request and PSR-17 response factory as second and third argument to the constructor of
  :php:class:`OCC\PSR15\QueueRequestHandler`, and an optional response factory as second argument to the constructor
  of :php:class:`OCC\PSR15\ErrorMiddleware`.

**Bugfixes:**

* :php:method:`OCC\PSR15\QueueRequestHandler::respond()` now sends every header value as a separate header line.
  Previously multiple values were combined into one comma-separated line, which broke responses setting more than one
  cookie. Headers already set by PHP (e.g. `Cache-Control` from `session_start()`) are now replaced instead of being
  sent twice, except for cookies.
* :php:method:`OCC\PSR15\QueueRequestHandler::respond()` now sends the status line after all other headers. Previously
  PHP silently changed the status code to `302` when a `Location` header was present (unless the status was `201` or
  `3xx`), and to `401` when a `WWW-Authenticate` header was present, e.g. turning a `202 Accepted` into a redirect.

**Maintenance:**

* Added a test suite for PHPUnit (run with `composer phpunit:test`), which is run for every supported PHP version (8.1
  to 8.5) on GitHub
* Excluded the documentation, tests and configuration files of development tools from the Composer package, so it only
  contains the source code, `composer.json`, `LICENSE` and `README.md`
* The documentation is no longer part of the repository, but built and deployed to
  `GitHub Pages <https://code.opencultureconsulting.com/psr-15/>`_ automatically for every release. To read it
  offline, build it locally with `composer phpdoc:build`

v1.3.1
======

**Maintenance:**

* Moved exception handling from :php:method:`OCC\PSR15\QueueRequestHandler::handle()` to separate method for better
  readability
* Updated links to `documentation <https://code.opencultureconsulting.com/psr-15/>`_

v1.3.0
======

**Minor Changes:**

* Added check in :php:method:`OCC\PSR15\AbstractMiddleware::process()` to ensure correct request handler type

**Maintenance:**

* Raised PHPStan rule level to 10

v1.2.1
======

**Maintenance:**

* Updated dependencies
* Added annotations, removed redundant type hints, and made classes final where appropriate

v1.2.0
======

**Minor Changes:**

* Added a `Warning` header to identify response messages from converted exceptions
* Removed the option to provide an exit status to :php:method:`OCC\PSR15\QueueRequestHandler::respond()`
* Added Composer command for development tools (PHP Mess Detector, Psalm Taint Analysis)

**Maintencance:**

* Updated dependencies

v1.1.0
======

**New Features:**

* Extended `documentation <https://code.opencultureconsulting.com/psr-15/>`_

**Minor Changes:**

* Added Composer commands for development tools (PHP_CodeSniffer, PHP-CS-Fixer, PHPStan, Psalm and phpDocumentor)

**Maintencance:**

* Updated dependencies

v1.0.1
======

**Maintenance:**

* Updated dependencies

v1.0.0
======

**Initial Release**
