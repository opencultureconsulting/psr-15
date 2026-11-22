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

**Bugfixes:**

* :php:method:`OCC\PSR15\QueueRequestHandler::respond()` now sends every header value as a separate header line.
  Previously multiple values were combined into one comma-separated line, which broke responses setting more than one
  cookie. Headers already set by PHP (e.g. `Cache-Control` from `session_start()`) are now replaced instead of being
  sent twice, except for cookies.

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
