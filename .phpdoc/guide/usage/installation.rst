.. title:: Installation

Installation
############

.. sidebar:: Table of Contents
  .. contents::

Composer
========

The intended and recommended way of re-using this package is via `Composer <https://getcomposer.org/>`_. The following
command will get you the latest version and make it a dependency of your project. It will also request all dependencies
and register all classes with the autoloader to make them available inside the application.

.. code-block:: shell

  # This will install the latest stable version suitable for your project
  composer require "opencultureconsulting/psr15"

If you want to use a specific version other than the latest available for your environment, you can do so by appending
the desired version constraint:

.. code-block:: shell

  # This will install the latest patch level version of 1.1 (i. e. >=1.1.0 && <1.2.0)
  composer require "opencultureconsulting/psr15:~1.1"

All available versions as well as further information about :doc:`requirements` and dependencies can be found on
`Packagist <https://packagist.org/packages/opencultureconsulting/psr15>`_.

Git
===

Alternatively, you can fetch the files from `GitHub <https://github.com/opencultureconsulting/psr-15>`_ and add them to
your project manually. The best way is by cloning the repository, because then you can easily update to a newer version
by just pulling the changes and checking out a different version tag.

.. code-block:: shell

  # This will clone the repository into the "psr15" directory
  git clone https://github.com/opencultureconsulting/psr-15.git psr15

If you want to use a specific version other than the latest development state, you have to specify the desired tag as
well:

.. code-block:: shell

  # This will clone the repository state at version "1.1.0" into the "psr15" directory
  git clone --branch=v1.1.0 https://github.com/opencultureconsulting/psr-15.git psr15

Be aware that you also need to make the classes available in your application by either adding them to your autoloader
or by including all files individually in PHP. Also don't forget to do the same for all :doc:`requirements`.

Download
========

As a last resort you can also just download the files. You can find all available versions as well as the current
development state on the `GitHub release page <https://github.com/opencultureconsulting/psr-15/releases>`_.
