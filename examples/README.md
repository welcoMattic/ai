# Symfony AI Examples

This directory contains various examples of how to use the Symfony AI components. They are meant to provide a
reference implementation to help you get started.

On top, the examples are used as integration tests to ensure that the components work as expected.

## Running the examples

For setting up and running the examples, you can either run them standalone or via the example runner. You find the
commands for that in this section. Make sure to change into the `examples` directory before running the commands.

```bash
cd examples
```

### Setup

#### Dependencies

Before running the examples, you need to install the dependencies. You can do this by running:

```bash
composer install
```

#### Configuration

Depending on the examples you want to run, you may need to configure the needed API keys. Therefore, you need to create a
`.env.local` file in the root of the examples' directory. This file should contain the environment variables for the
corresponding example you want to run.

_Now you can run examples standalone or via the example runner._

#### Store with Docker

Some of the store examples require locally running services, meaning that you need to have Docker installed and running
to test these examples.

```bash
docker compose up -d
```

### Running examples standalone

Every example script is a standalone PHP script that can be run from the command line.
You can run an example by executing the following command:

```bash
php openai/openai/chat.php
```

### Running examples via the example runner

You can also run the examples via the example runner, which takes care of running the examples parallel in
sub-processes. This is useful if you are contributing to the Symfony AI components and want to ensure that all examples
work as expected.

You can run the example runner by executing the following command:

```bash
./runner
```

If you want to run only examples of a specific subdirectory, you can pass the subdirectory name as an argument:

```bash
./runner openai
```
