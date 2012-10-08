# Heartsentwined\FileSystemManager

[![Build Status](https://secure.travis-ci.org/heartsentwined/file-system-manager.png)](http://travis-ci.org/heartsentwined/file-system-manager)

A collection of often-used file and directory management functions not present in PHP core.

# Installation

[Composer](http://getcomposer.org/):

```json
{
    "require": {
        "heartsentwined/file-system-manager": "1.*"
    }
}
```

# Usage

Recursively iterate the directory `foo`, listing all files (child-last):

```php
use \Heartsentwined\FileSystemManager\FileSystemManager;
foreach (FileSystemManager::fileIterator('foo') as $file) {
    echo $file; // /path/to/file
}
```

Recursively iterate the directory `foo`, listing all directories (child-first):

```php
use \Heartsentwined\FileSystemManager\FileSystemManager;
foreach (FileSystemManager::dirIterator('foo') as $dir) {
    echo $dir; // /path/to/child/dir
}
```

Recursive [rmdir](http://php.net/manual/en/function.rmdir.php): remove the directory `foo` along with all child directories and files

```php
use \Heartsentwined\FileSystemManager\FileSystemManager;
FileSystemManager::rrmdir('foo');
```

Recursive [copy](http://php.net/manual/en/function.copy.php): copy the directory `foo` to `bar` along with all child directories and files

**Warning: this function overwrites existing files**

```php
use \Heartsentwined\FileSystemManager\FileSystemManager;
FileSystemManager::rcopy('foo', 'bar');
```

`rcopy` will copy into existing directories if they already exist. By default, it will create non-existent directories with permission `0755`. You can change this by specifying the third parameter:

```php
FileSystemManager::rcopy('foo', 'bar', 0777);
```

Recursive [chmod](http://php.net/manual/en/function.chmod.php): chmod the directory `foo` to `0755`, along with all child directories and files

```php
use \Heartsentwined\FileSystemManager\FileSystemManager;
FileSystemManager::rchmod('foo', 0755);
```

Recursive [chown](http://php.net/manual/en/function.chown.php): chown the directory `foo` to `www-data`, along with all child directories and files

```php
use \Heartsentwined\FileSystemManager\FileSystemManager;
FileSystemManager::rchown('foo', 'www-data');
```

Recursive [chgrp](http://php.net/manual/en/function.chgrp.php): chgrp the directory `foo` to `www-data`, along with all child directories and files

```php
use \Heartsentwined\FileSystemManager\FileSystemManager;
FileSystemManager::rchown('foo', 'www-data');
```
