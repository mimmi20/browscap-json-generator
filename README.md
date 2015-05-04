Browser Capabilities Project
============================

This additional tool is used to build and maintain a proprocessed browscap.json file which is used for browscap.js.

## Install

```
$ git clone git://github.com/mimmi20/browscap-json-generator.git
$ cd browscap-json-generator
$ curl -s https://getcomposer.org/installer | php
$ php composer.phar install
```

## Usage

```
bin/browscap-with-json build [version]
```

For further documentation on the `build` command, [see here](https://github.com/browscap/browscap/wiki/Build-Command).

## Demonstrating Functionality

You can export a new set of browscap.* files from the JSON files:

```
$ bin/browscap-with-json build 5020-test
Resource folder: <your source dir>
Build folder: <your target dir>
Generating full_asp_browscap.ini [ASP/FULL]
Generating full_php_browscap.ini [PHP/FULL]
Generating browscap.ini [ASP]
Generating php_browscap.ini [PHP]
...
All done.
$
```

## Directory Structure

* `bin` - Contains executable files
* `build` - Contains various builds
* `resources` - Files needed to build the various files, also used to validate the capabilities
* `src` - The code of this project lives here

## Contributing

For instructions on how to contribute see the [CONTRIBUTE.md](https://github.com/browscap/browscap/blob/master/CONTRIBUTE.md) file.

## License

See the [LICENSE](https://github.com/browscap/browscap/blob/master/LICENSE) file.
