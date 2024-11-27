# Multiple error formatters for PHPStan

This project was born form a need to have multiple outputs from a PHPStan run. Its goal is to provide a way to direct outputs to multiple locations, without having to run PHPStan multiple times.

## Installation

```bash
composer require --dev backendtea/phpstan-multiple-error-formatter
```

If you also install [phpstan/extension-installer](https://github.com/phpstan/extension-installer) then you're all set!

<details>
  <summary>Manual installation</summary>

If you don't want to use `phpstan/extension-installer`, include extension.neon in your project's PHPStan config:

```
includes:
    - vendor/backendtea/phpstan-multiple-error-formatter/config.neon
```
</details>

## Usage

This package provides the 'multiple' error formatter. Generally you want to use this in CI by adding the `--error-format multiple` flag.

The `multiple` error formatter wil look for the `errorFormatters.formatters` configuration option, where you can define the outputs you are looking for

This package defines the following formatters to output to a file:

* jsonFile : prints a json file
* prettyJsonFile: prints a pretty json file (with whitespaces for human readability)
* gitlabFile: outputs a file for gitlab code quality inspections


You can configure the file location it should output to with the `errorFormatters.jsonFile` and `errorFromatters.gitlabFile` configuration options:


An example where we want to write a pretty json file, a gitlab file, and output to a table in stdOut would look like this: 
```yaml
parameters:
    errorFormatters:
        formatters:
            - table
            - gitlabFile
            - prettyJsonFile
        jsonFile: %currentWorkingDirectory%/phpstan.json
        gitlabFile: %currentWorkingDirectory%/phpstan-gitlab.json
      # other config...
```