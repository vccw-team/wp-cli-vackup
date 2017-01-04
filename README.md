# wp vackup

[![Build Status](https://travis-ci.org/vccw-team/wp-cli-vackup.svg?branch=master)](https://travis-ci.org/vccw-team/wp-cli-vackup)

This is a WP-CLI based backup solution for WordPress.

This command is friendly with aliases of the WP-CLI like following.

```
$ wp @all vackup create --dir=backups/
```


## Requires

* WP-CLI 0.23 or later

## Getting Started

```bash
$ wp package install vccw/vackup:@stable
```

## Subcommands

* `wp vackup create`: Create a .zip archive from WordPress. It contains files and database.
* `wp vackup extract`: Extract the WordPress site from a .zip archive.

### Backup your WordPress files and database.

The file name of the archive will be generated from `home_url()` and timestamp.

```bash
$ wp vackup archive create --dir=path/to/dir
```

Then archive should be `path/to/dir/example.com-20170101000000.zip`.

You can use it with alias of the WP-CLI.

```
$ wp @all archive create --dir=backups/
```

### Extract from backup.

```bash
$ wp vackup archive extract <file> [--delete] [--exclude=<files>]
```

If you add `--delete` option, this command will remove all files before extracting.

```bash
$ wp vackup archive extract /path/to/archive.zip --delete
```

You can exclude specific files from archive.

```bash
$ wp vackup archive extract /path/to/archive.zip --exclude=wp-config.php
```

### Help

```bash
$ wp help vackup

NAME

  wp vackup

DESCRIPTION

  Manage archives for the Shifter.

SYNOPSIS

  wp vackup <command>

SUBCOMMANDS

  create       Create a .zip archive from your WordPress.
  extract      Extract the WordPress site from a .zip archive.
```

## Installing manually

```bash
$ mkdir -p ~/.wp-cli/commands && cd -
$ git clone git@github.com:vccw-team/wp-cli-vackup.git
```

Add following into your `~/.wp-cli/config.yml`.

```yaml
require:
  - commands/wp-cli-vackup/cli.php
```

## Upgrade

```
$ wp package update
```
