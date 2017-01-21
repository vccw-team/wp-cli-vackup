# wp vackup

[![Build Status](https://travis-ci.org/vccw-team/wp-cli-vackup.svg?branch=master)](https://travis-ci.org/vccw-team/wp-cli-vackup)

This is a WP-CLI based backup solution for WordPress.

This command is friendly with aliases of the WP-CLI like following.

```
$ wp @all vackup create
```

## Requires

* WP-CLI 0.23 or later

## Getting Started

```bash
$ mkdir -p ~/.wp-cli/commands && cd -
$ git clone git@github.com:vccw-team/wp-cli-vackup.git
```

Add following into your `~/.wp-cli/config.yml`.

```yaml
require:
  - commands/wp-cli-vackup/cli.php
```

## Configuration

You can configure the default directory to store backups.

```
vackup:
  dir: /Users/miya0001/backups
```

See more about configuration: http://wp-cli.org/config/

## Subcommands

* `wp vackup create`: Create a .zip archive from WordPress. It contains files and database.
* `wp vackup extract`: Extract the WordPress site from a .zip archive.

### Backup your WordPress files and database.

The file name of the archive will be generated from `home_url()` and timestamp.

```bash
$ wp vackup create --dir=path/to/dir
```

Then archive should be `path/to/dir/example.com-20170101000000.zip`.

### Extract from backup.

```bash
$ wp vackup extract <file>
```

You sometimes need `wp search-replace`.

### Launch WordPress from backup with PHP built-in server.

```
$ wp vackup server /path/to/backup.zip
```

Then visit [http://localhost:8080/](http://localhost:8080/).

### Usage

```bash
NAME

  wp vackup

DESCRIPTION

  CLI based backup solution for WordPress

SYNOPSIS

  wp vackup <command>

SUBCOMMANDS

  create       Create a `.zip` archive from your WordPress.
  restore      Restore the WordPress site from backup.
  server       Launch WordPress from backup file with PHP built-in web server.
```

## Upgrade

```
$ wp package update
```
