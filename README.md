# Merge multiple PHPUnit reports into one file

[![Latest Stable Version](https://img.shields.io/packagist/v/nimut/phpunit-merger.svg)](https://packagist.org/packages/nimut/phpunit-merger)
[![StyleCI](https://styleci.io/repos/114540931/shield?branch=master)](https://styleci.io/repos/114540931)

Sometimes it is necessary to run multiple PHPUnit instances to execute all tests of a project. Unfortunately each run
writes its own coverage and log reports. There is no support in PHPUnit to merge the reports of multiple runs.

This project provides two commands to merge coverage files as well as log files. It was designed to provide merged
reports to e.g. SonarQube Scanner for further processing. 

## Installation

Use [Composer](https://getcomposer.org/) to install the testing framework.

```bash
$ composer require --dev nimut/phpunit-merger
```

Composer will add the package as a dev requirement to your composer.json and install the package with its dependencies.

## Usage

### Coverage

The coverage command merges files containing PHP_CodeCoverage objects into one file in Clover XML format.

```bash
$ vendor/bin/phpunit-merger coverage <directory> <output> <type>
```

**Arguments**

- `directory`: Provides the directory containing one or multiple files with PHP_CodeCoverage objects
- `output`: Output where the merged result should be stored. This can be a file `foo.xml` or directory name
- `type`: Output type default value is `clover`. This can be `clover` or `html`

### Log

The log command merges files in JUnit XML format into one file in JUnit XML format.

```bash
$ vendor/bin/phpunit-merger log <directory> <file>
```

**Arguments**

- `directory`: Provides the directory containing one or multiple files in JUnit XML format 
- `file`: File where the merged result should be stored
