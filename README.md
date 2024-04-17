# Queue-based HTTP Server Request Handler

***An implementation of [PSR-15: HTTP Server Request Handlers](https://www.php-fig.org/psr/psr-15/).***

The PHP Standard Recommendation PSR-15 defines interfaces for server request handlers and proposes a queue-based implementation using different middlewares for processing requests and preparing responses. This package follows those guidelines and provides a [HTTP server request handler](src/QueueRequestHandler.php) implementation using a [middleware queue](src/MiddlewareQueue.php). It also contains an [abstract class for middlewares](src/AbstractMiddleware.php) to ease the process of writing your own middleware, but you can just as well use any middleware that implements `Psr\Http\Server\MiddlewareInterface` specified by PSR-15 (e.g. from the awesome [PSR-15 HTTP Middlewares](https://github.com/middlewares) project).

All components of this package follow the highest coding standards of [PHPStan](https://phpstan.org/) and [Psalm](https://psalm.dev/), and comply to [PSR-12](https://www.php-fig.org/psr/psr-12/) code style guidelines to make sure they can be combined and easily re-used in other projects.

## Quick Start

The intended and recommended way of re-using this package is via [Composer](https://getcomposer.org/). The following command will get you the latest version:

    composer require opencultureconsulting/psr15

All available versions as well as further information about requirements and dependencies can be found on [Packagist](https://packagist.org/packages/opencultureconsulting/psr15).

## Full Documentation

The full documentation is available on [GitHub Pages](https://opencultureconsulting.github.io/psr-15/) or alternatively in [doc/](doc/).

## Quality Gates

[![PHPCS](https://github.com/opencultureconsulting/psr-15/actions/workflows/phpcs.yml/badge.svg)](https://github.com/opencultureconsulting/psr-15/actions/workflows/phpcs.yml)
[![PHPMD](https://github.com/opencultureconsulting/psr-15/actions/workflows/phpmd.yml/badge.svg)](https://github.com/opencultureconsulting/psr-15/actions/workflows/phpmd.yml)

[![PHPStan](https://github.com/opencultureconsulting/psr-15/actions/workflows/phpstan.yml/badge.svg)](https://github.com/opencultureconsulting/psr-15/actions/workflows/phpstan.yml)
[![Psalm](https://github.com/opencultureconsulting/psr-15/actions/workflows/psalm.yml/badge.svg)](https://github.com/opencultureconsulting/psr-15/actions/workflows/psalm.yml)
