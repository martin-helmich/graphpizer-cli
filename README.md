GraPHPizer: Analytics engine for PHP source code (CLI tool)
===========================================================

**Disclaimer**: This project is actively developed and by no means stable. It is
completely undocumented. I refuse to take any responsibility for any kind of
havoc this program might wreak on your computer and to provide any kind of
support.

## Author and License

Martin Helmich  
This project is [GPL-licensed](LICENSE).

**Background:** This project started off as part of my (not-yet-complete) master's thesis in CS at the [University of Applied Sciences Osnabrück][hsos]. It has proven it's worth there and I'll probably continue to develop and maintain it.

## What is GraPHPizer?

This is a command-line tool that's designed for usage with the [GraPHPizer
server application](https://github.com/martin-helmich/graphpizer-server). A
general description of what GraPHPizer is and what it does can be found there.

## Installation

### Prerequisites

- PHP in version 5.5 or newer
- [Composer](http://getcomposer.org) in a halfway recent version
- A [GraPHPizer server](https://github.com/martin-helmich/graphpizer-server)
  running on a network-reachable machine

### Install using composer

You can install the GraPHPizer CLI using Composer:

```shellsession
$ composer require helmich/graphpizer-cli
```

You can also install the CLI globally:

```shellsession
$ composer global require helmich/graphpizer-cli
```

After installation you will find a `graphpizer` executable in your *bin* dir
(when installing locally, this will typically be `$PWD/vendor/bin`. When
installing globally, it will be `$HOME/.composer/bin`).

## Configuration

### CLI flags

The `graphpizer` command-line utility offers a variety of flags and parameters
that can be set on invocation:

- `--graph-host` or `-H` configures the GraPHPizer server name. The default
  value is `localhost`.
- `--graph-port` or `-P` configures the GraPHPizer port. The default value is
  `9000`

### The `graphpizer.json` configuration file(s)

For a per-project configuration, you can also create a `graphpizer.json` file
inside your project root directory. You can also create additional
`graphpizer.json` files in sub-directories of your project; these configurations
will be applied to that directory and it's sub-directories only.

See the [respective section in the GraPHPizer server documentation](https://github.com/martin-helmich/graphpizer-server/wiki/Source-import-configuration) for more information.

## Usage

The `graphpizer` CLI tool offers a set of commands that can be called. The most
important is the `import:ast` command which is invoked as follows:

    graphpizer import:ast [--prune] <path-to-project>...

You can specify any number of diretories or files as arguments to the
`import:ast` call. Furthermore, you can set the `--prune` flag when you don't
want incremental source code import.

[hsos]: https://www.hs-osnabrueck.de/
