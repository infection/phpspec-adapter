# PHPSpec Test Framework Adapter for Infection

This package provides the test framework adapter for [infection][infection].

![Architecture](./docs/test-framework-adapter.png)


## Installation

In a standard usage, infection should detect [`phpspec/phpspec`][PHPSpec] being used and
leverage its [`infection/extension-installer`][infection/extension-installer] to install this
package.

Otherwise, you can still install it as usual:

```bash
composer require --dev infection/phpspec-adapter
```

The adapter will be automatically registered in Infection's runtime through its auto-discovery mechanism.


## Usage

Once installed, you can run Infection with [PHPSpec][PHPSpec]:

```bash
vendor/bin/infection --test-framework=phpspec
```

Infection will automatically detect and use the PHPSpec adapter when PHPSpec is configured in your project.

### Configuration

The adapter works with your existing PHPSpec configuration. No additional configuration is required beyond the standard
Infection configuration file (`infection.json.dist` or `infection.json`).

For more information on configuring Infection, see
the [Infection documentation](https://infection.github.io/guide/usage.html).


## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](.github/CONTRIBUTING.md) for details.


## License

This project is licensed under the BSD 3-Clause License. See the [LICENSE](LICENSE) file for details.


[infection]: https://infection.github.io
[infection/extension-installer]: https://packagist.org/packages/infection/extension-installer
[PHPSpec]: https://packagist.org/packages/phpspec/phpspec
