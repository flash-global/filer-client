# Filer Client

This is the client you should use for consuming the Filer service.

The client can use two kinds of transports to send requests:

* Asynchronous transport implemented by `BeanstalkProxyTransport`
* Synchronous transport implemented by `BasicTransport`

`BeanstalkProxyTransport` delegate the API consumption to workers by sending file properties to a Beanstalkd queue.

`BasicTransport` use the _classic_ HTTP layer to send files.

If asynchronous transport is set, it will act as default transport. Synchronous transport will be a fallback in case
when asynchronous transport fails.

All examples in this document will use `BeanstalkProxyTransport` and `BasicTransport`.

## Installation

Filer Client needs **PHP 5.5** or higher.

Add this requirement to your `composer.json`: `"fei/filer-client": : "^1.0"`

Or execute `composer.phar require fei/filer-client` in your terminal.

If you want use the asynchronous functionality of the Filer client (and we know you want), you need an instance of
[Beanstalkd](http://kr.github.io/beanstalkd/) which running properly and an instance of `api-client-worker.php` which
will consume the Beanstalk's pipe and forward messages payload to the Filer API:

```
Filer Client -> Beanstalkd -> api-client-worker.php -> Filer API server
```

### Beanstalkd configuration

Running Beanstalkd is very simple. However, you must pay attention to the `z` option which set the maximum job
(or message) size in bytes. So, if you want transfer 100 Mo files to Filer API, you should consider at least a value of
16O Mo for the `z` parameter.

```
beanstalkd -z 167772160
```

### Run `api-client-worker.php`

You could see below an example of running `api-client-worker.php`:

```
php /path/to/filer-client/vendor/bin/api-client-worker.php --host 127.0.0.1 --port 11300 --delay 3
```

| Options | Shortcut | Description                                   | Default     |
|---------|----------|-----------------------------------------------|-------------|
| host    | `-h`     | The host of Beanstalkd instance               | `localhost` |
| port    | `-p`     | The port which Beanstalkd instance listening  | `11300`     |
| delay   | `-d`     | The delay between two treatment of the worker | 3 seconds   |
| verbose | `-v`     | Print verbose information                     | -           |

You can control the `api-client-worker.php` process by using [Supervisor](http://supervisord.org/index.html).

## Entities and classes

### File entity

In addition to traditional ID and CreatedAt fields, File Entity has **six* important properties:

| Properties    | Type              |
|---------------|-------------------|
| id            | `integer`         |
| createdAt     | `datetime`        |
| filename      | `string`          |
| uuid          | `string`          |
| revision      | `integer`         |
| category      | `integer`         |
| contentType   | `string`          |
| data          | `string`          |
| file          | `string`          |
| contexts      | `ArrayColleciton` |

- `$uuid` (Universal Unique Identifier) is a **unique id** corresponding to a file. Its format is based on
  **36 characters** as defined in `RFC4122` prefixed by a **backend id** and separated by a `:`.
  Example: `bck1:f6461366-a414-4b98-a76d-d7b190252e74`
- `filename` is a string indicating the file's filename.
- `revision` is an integer indicating the file's current revision.
- `category` is an integer defining in which database the file will be stored in.
- `contentType` defines the content type of the `File` object.
- `data` contains the file's content.
- `file` is an `SplFileObject` instance. (see https://secure.php.net/manual/en/class.splfileobject.php for more details)
- `contexts` is an `ArrayCollection` instance where each element is a Context entity

## Basic usage

In order to consume `File` method, you have to define a new `Filer` instance and set transport type (Async and Sync):

```php
<?php

use Fei\Service\Filer\Client\Filer;
use Fei\ApiClient\Transport\BasicTransport;
use Fei\ApiClient\Transport\BeanstalkProxyTransport;
use Pheanstalk\Pheanstalk;

$filer = new Filer([Filer::OPTION_BASEURL => 'https://filer.api.com']); // Put your filer API base URL here
$filer->setTransport(new BasicTransport());

$proxy = new BeanstalkProxyTransport();
$proxy->setPheanstalk(new Pheanstalk('127.0.0.1'));

$filer->setAsyncTransport($proxy);

// Use the filer client...
```

Filer client instance will first attempt to transfer the messages with Beanstalkd, if the process fail then the client
will try to send File payload directly to the right API endpoint.

There are several methods in `Filer` class, all listed in the following table:

| Method  | Parameters                                 | Return             |
|---------|--------------------------------------------|--------------------|
|upload   | `File $file`, `$flags = null`              | `null` or `string` |
|retrieve | `string $uuid`                             | `null` or `File`   |
|delete   | `string $uuid`                             | `null`             |
|truncate | `string $uuid`, `int $keep = 0`            | `null`             |
|serve    | `string $uuid`                             | `string`           |
|save     | `string $uuid`, `string $path` `string as` | `null`             |
|embed    | `string $path`                             | `null` or `File`   |

`$uuid` (Universal Unique Identifier) is a **unique id** corresponding to a file. (see **File entity** part)

### Client option

Only one option is available which can be passed to the constructor or `Filer::setOptions(array $options)` methods:

| Option         | Description                                    | Type   | Possible Values                                | Default |
|----------------|------------------------------------------------|--------|------------------------------------------------|---------|
| OPTION_BASEURL | This is the server to which send the requests. | string | Any URL, including protocol but excluding path | -       |

**Note:** All the examples below are also available in `examples` directory.

### Search the files

You can search the files directly from the client with `Filer::search($builder)`

- `$builder` must be a `SearchBuilder` instance

You can create your searches using the SearchBuilder to make the search easier to use.

```php
<?php

use Fei\Service\Filer\Client\Filer;
use Fei\Service\Filer\Client\Builder\SearchBuilder;

// Creating a Filer client instance...
$filer = new Filer([Filer::OPTION_BASEURL => 'http://127.0.0.1:8080']);

$builder = new SearchBuilder();
$builder->category()->equal(1);
$builder->context()->key('test 1')->equal('test 1');
$builder->context()->key('test 2')->equal('test 2');
$builder->filename()->beginsWith('avatar');

$files = $filer->search($builder);
```

As you can see mutiple methods are available like `filename` and multiple operators methods too like :

- `like` : to use the `LIKE` operator
- `equal` : to use the `=` operator
- `beginsWith` : to use the `LIKE` operator and the string has to start by the value specified
- `endsWith` : to use the `LIKE` operator and the string has to end by the value specified

Note that you can search on multiple categories by adding multiple categories filter :

```php
$builder->category()->equal(1);
$builder->category()->equal(2);
$builder->category()->equal(3);
```

Note that you can filter on contexts. By default if you have multiple filter for the contexts, an "AND" condition will be processed. You can choose to do an "OR" condition by making the following filter :

```php
    $searchBuilder->contextCondition('OR');
```

You can also search a file by giving an uuid. If you do this king of search you don't need to filter by categories but you still can if you want.

Here is an example on how to search a file with it's uuid :

```php
$builder->uuid()->equal('bck1:30d6a8ed-f9cf-4a6d-a76e-04ec941d1f45');
```

### Upload a new file

You can upload a file instance with `Filer::upload($file, $flags)`:

- `$file` must be a `File` instance
- `$flags` can be null or a `Filer` constant:
    - `Filer::ASYNC_UPLOAD`: prepare the upload without waiting for API response
    - `Filer::NEW_REVISION`: create a new revision for a specific `uuid` (which must obviously be declared in `$file`)

The function will return `null` or a `string` describing the new file path.

#### Example

```php
<?php

use Fei\Service\Filer\Entity\File;
use Fei\Service\Filer\Client\Filer;

// Creating a Filer client instance...

$file = new File();
$file->setCategory(File::CATEGORY_IMG)
    ->setContexts(['test 1' => 'test 1', 'test 2' => 'test 2'])
    ->setFile(new SplFileObject(__DIR__ . '/../file/path.pdf');

$uuid = $filer->upload($file, Filer::ASYNC_UPLOAD);

// Add a new revision:

$uuid = $filer->upload(
    (new File())
        ->setCategory(File::CATEGORY_IMG)
        ->setUuid($uuid)
        ->setFile(new SplFileObject(__DIR__ . '/../file/path2.pdf')),
    Filer::NEW_REVISION
);
```

An other way to create a `File` instance is to use the static method `Filer::embed($path)` which allows you to create a
file instance using a local file path:

```php
<?php

use Fei\Service\Filer\Client\Filer;

// Creating a Filer client instance...

$file = Filer::embed('/path/to/the/file.pdf');
$file = $filer->upload($file, Filer::ASYNC_UPLOAD);
```

### Retrieve a file

You can retrieve a file instance with `Filer::retrieve($uuid)`. The only necessary parameter is a valid file `uuid`.

This method returns a `File` instance or null if the file was not found.

#### Example

```php
<?php

// Creating a Filer client instance...

$file = $filer->retrieve('bck1:f6461366-a414-4b98-a76d-d7b190252e74');
```

### Delete a file

You can delete a file with `Filer::delete($uuid)`. The only necessary parameter is a valid file `uuid`.

#### Example

```php
<?php

use Fei\Service\Filer\Entity\File;

// Creating a Filer client instance...

$filer->delete('bck1:f6461366-a414-4b98-a76d-d7b190252e74');

echo $filer->retrieve('bck1:f6461366-a414-4b98-a76d-d7b190252e74') instanceof File; // false
```

### Truncate a file

The `Filer::truncate($uuid, $keep = 0)` method allows you to remove every revisions of a file, except a determined
number of revisions.

#### Example

```php
<?php

use Fei\Service\Filer\Entity\File;
use Fei\Service\Filer\Client\Filer;

// Creating a Filer client instance...

$uuid = $filer->upload(
    (new File())
        ->setCategory(File::CATEGORY_IMG)
        ->setContexts(['test 1' => 'test 1', 'test 2' => 'test 2'])
        ->setFile(new SplFileObject(__DIR__ . '/../tests/_data/avatar.png')),
    Filer::ASYNC_UPLOAD
);

$uuid = $filer->upload(
    (new File())
        ->setCategory(File::CATEGORY_IMG)
        ->setUuid($uuid)
        ->setFile(new SplFileObject(__DIR__ . '/../tests/_data/capture.png')),
    Filer::ASYNC_UPLOAD|Filer::NEW_REVISION
);

// truncate the file and keep the last revisions
$filer->truncate($uuid, 1);
```

### Save

`Filer::save($uuid, $path, $as = null)` method is a way to retrieve and save a local copy of a remote File.

#### Example

```php
<?php

use Fei\Service\Filer\Entity\File;

// Creating a Filer client instance...

$uuid = $filer->upload(
    (new File())
        ->setCategory(File::CATEGORY_IMG)
        ->setContexts(['test 1' => 'test 1', 'test 2' => 'test 2'])
        ->setFile(new SplFileObject(__DIR__ . '/../tests/_data/file.png'))
);

// save the file at this location on the local server
$filer->save($uuid, __DIR__ . '/../tests/_data/save/file_saved.png');
```

### Serve a file

With the method `Filer::serve($uuid, $flag = Filer::FORCE_DOWNLOAD)` you can serve a file to user agents and force the download.

If you don't want to download the file and show it, you can call the method like this: `Filer::serve($uuid, Filer::FORCE_INLINE)`

#### Example

```php
<?php

use Fei\Service\Filer\Entity\File;

// Creating a Filer client instance...

$uuid = $filer->upload(
    (new File())
        ->setCategory(File::CATEGORY_IMG)
        ->setContexts(['test 1' => 'test 1', 'test 2' => 'test 2'])
        ->setFile(new SplFileObject(__DIR__ . '/../tests/_data/avatar.png'))
);

// Serve the file
$filer->serve($uuid);
```
