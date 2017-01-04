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
$ wp package install vccw/vackup:@stable
```

### Manual Install

```bash
$ mkdir -p ~/.wp-cli/commands && cd -
$ git clone git@github.com:vccw-team/wp-cli-vackup.git
```

Add following into your `~/.wp-cli/config.yml`.

```yaml
require:
  - commands/wp-cli-vackup/cli.php
```

## Subcommands

* `wp vackup create`: Create a .zip archive from WordPress. It contains files and database.
* `wp vackup extract`: Extract the WordPress site from a .zip archive.

### Backup your WordPress files and database.

The file name of the archive will be generated from `home_url()` and timestamp.

```bash
$ wp vackup vackup create --dir=path/to/dir
```

Then archive should be `path/to/dir/example.com-20170101000000.zip`.

### Create backups with SSH aliases

If you want to create backups on the remote server with SSH alias.

Please configure like following in the `~/.wp-cli/config` in remote servers.

```
vackup:
  dir: /Users/miyauchi/Desktop/backups
```

Also you have to configure aliases in your machine.

http://wp-cli.org/commands/cli/alias/

Then you can run like following.

```
$ wp @all vackup create
```

### Extract from backup.

```bash
$ wp vackup extract <file>
```

You sometimes need `wp search-replace`.

### Help

```bash
$ wp help vackup

NAME

  wp vackup

DESCRIPTION

  CLI based backup solution for WordPress

SYNOPSIS

  wp vackup <command>

SUBCOMMANDS

  create       Create a .zip archive from your WordPress.
  extract      Extract the WordPress site from a .zip archive.
```

## Upgrade

```
$ wp package update
```
