# wp vackup

[![Build Status](https://travis-ci.org/vccw-team/wp-cli-vackup.svg?branch=master)](https://travis-ci.org/vccw-team/wp-cli-vackup)

This is a WP-CLI based backup solution for WordPress. You can create backups of the files and the DB, and restore the sites back from them.

With the alias function of the WP CLI, this command helps you to reduce workload on managing multiple sites.

```
$ wp @all vackup create
```

See more about alias: https://make.wordpress.org/cli/handbook/config/#config-files

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

See more about configuration: https://make.wordpress.org/cli/handbook/config/

## Subcommands

* `wp vackup create`: Create a .zip archive from WordPress. It contains files and database.
* `wp vackup restore`: Restore the WordPress site from a .zip archive.
* `wp vackup server`: Launch a temporary WordPress site with PHP built-in web server.

### Backup your WordPress files and database.

The file name of the archive will be generated from `home_url()` and a timestamp.

```bash
$ wp vackup create --dir=path/to/dir
```

The archive file name will be `path/to/dir/example.com-20170101000000.zip`.

### Restore from backup.

```bash
$ wp vackup restore <file>
```

You'd sometimes need `wp search-replace`, for example when you create a backup from production and restore it to dev environment.

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

  create       Create a `.zip` archive of a WordPress install.
  restore      Restore the WordPress site from backup.
  server       Launch WordPress from backup file with PHP built-in web server.
```

## Upgrade

```
$ wp package update
```
