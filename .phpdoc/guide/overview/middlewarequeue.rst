.. title:: MiddlewareQueue

MiddlewareQueue
###############

.. sidebar:: Table of Contents
  .. contents::

The `MiddlewareQueue` manages the middlewares involved in processing a server request. It makes sure they are called in
first-in, first-out (FIFO) order, i.e. the same order they were added to the queue. It also ensures all middlewares are
implementing the `PSR-15: HTTP Server Request Handlers <https://www.php-fig.org/psr/psr-15/>`_ specification for the
`Psr\Http\Server\MiddlewareInterface`.

When instantiating a `MiddlewareQueue` it defaults to being empty. But you can optionally pass an iterable set of
middlewares to the constructor which are then put into the queue. To demonstrate, the following examples both have
exactly the same result.

  Examples:

  .. code-block:: php
    use OCC\PSR15\MiddlewareQueue;

    $middlewares = [
        new MiddlewareOne(),
        new MiddlewareTwo()
    ];

    $queue = new MiddlewareQueue($middlewares);

  .. code-block:: php
    use OCC\PSR15\MiddlewareQueue;

    $queue = new MiddlewareQueue();

    $queue->enqueue(new MiddlewareOne());
    $queue->enqueue(new MiddlewareTwo());

The `MiddlewareQueue` is based on a `OCC\Basics\DataStructures\StrictQueue`.

Methods
=======

The `MiddlewareQueue` provides a set of API methods, with `MiddlewareQueue::enqueue()` and `MiddlewareQueue::dequeue()`
being the most relevant ones. The former will add a new item at the end of the queue while the latter removes and
returns the first item from the queue.

For a complete API documentation have a look at the
`StrictQueue <https://code.opencultureconsulting.com/php-basics/classes/OCC-Basics-DataStructures-StrictQueue.html>`_.

Adding a Middleware
-------------------

Invoking `MiddlewareQueue::enqueue()` will add a middleware at the end of the queue. The only argument must be a
middleware object implementing the `Psr\Http\Server\MiddlewareInterface`. If the given argument does not meet the
criterion an `OCC\Basics\DataStructures\Exceptions\InvalidDataTypeException` is thrown.

Have a look at the :doc:`abstractmiddleware` for an easy way to create your own middlewares!

Fetching the next in line
-------------------------

Calling `MiddlewareQueue::dequeue()` will return the first middleware from the queue, i.e. the oldest one on the queue.
Also, this middleware is removed from the queue.

If the queue is empty a `RuntimeException` is thrown, so make sure to check the queue's length (e.g. with `count()`)
before trying to dequeue an item!
