<?php

namespace Vackup;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

use WP_CLI_Command;
use WP_CLI;

require_once( dirname( __FILE__ ) . "/lib/Functions.php" );

/**
 * CLI based backup solution for WordPress
 *
 * @subpackage commands/community
 * @maintainer VCCW Team
 */
class CLI extends WP_CLI_Command
{
	/**
	 * Create a `.zip` archive from your WordPress.
	 *
	 * ## OPTIONS
	 *
	 * [--dir=<path>]
	 * : Path to the directory that you want to store archives.
	 * * The default value is `~/backups`
	 *
	 * ## EXAMPLES
	 *
	 *   $ wp vackup create
	 *   Success: Archived to '/home/user/backups/example.com-20170101022305.zip'.
	 *
	 *   $ wp vackup create --dir=path/to
	 *   Success: Archived to 'path/to/example.com-20170101022305.zip'.
	 *
	 * @subcommand create
	 */
	function create( $args, $assoc_args )
	{
		$backup_dir = untrailingslashit( Functions::tempdir( 'VAK' ) );

		WP_CLI::launch_self(
			"db export",
			array( $backup_dir . "/wordpress.sql" ),
			array(),
			true,
			true,
			array( 'path' => WP_CLI::get_runner()->config['path'] )
		);

		file_put_contents(
			$backup_dir . '/manifest.json',
			Functions::create_manifest()
		);

		$extra_config = WP_CLI::get_runner()->extra_config;

		$archive_dir = Functions::get_archive_path( $assoc_args, $extra_config );
		$archive = $archive_dir . '/' . Functions::get_archive_file_name();

		$res = Functions::create_archive( $archive, $backup_dir );

		Functions::rrmdir( $backup_dir );
		if ( is_wp_error( $res ) ) {
			WP_CLI::error( $res->get_error_message() );
		}

		WP_CLI::success( sprintf( "Archived to '%s'.", $res ) );
	}

	/**
	 * Restore the WordPress site from backup.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : The name of the backup file.
	 *
	 * ## EXAMPLES
	 *
	 *   $ wp vackup restore path/to/example.com-20170101022305.zip
	 *   Success: Restore from 'path/to/example.com-20170101022305.zip'.
	 *
	 * @subcommand restore
	 */
	function restore( $args, $assoc_args )
	{
		if ( ! is_file( $args[0] ) ) {
			WP_CLI::error( "No such file or directory." );
		}

		$tmp_dir = Functions::tempdir( 'VAK' );
		$res = Functions::unzip( $args[0], $tmp_dir );
		if ( is_wp_error( $res ) ) {
			WP_CLI::error( $res->get_error_message() );
		}

		if ( ! is_dir( $tmp_dir . '/wordpress' ) || ! is_file( $tmp_dir . '/wordpress.sql' ) ) {
			Functions::rrmdir( $tmp_dir );
			WP_CLI::error( sprintf( "Can't extract from '%s'.", $args[0] ) );
		}

		Functions::rempty( WP_CONTENT_DIR, array( "wp-config.php" ) );
		Functions::rcopy( $tmp_dir . '/wordpress', ABSPATH, array() );

		if ( is_file( $tmp_dir . "/wordpress.sql" ) ) {
			$result = WP_CLI::launch_self(
				"db import",
				array( $tmp_dir . "/wordpress.sql" ),
				array(),
				true,
				true,
				array( 'path' => WP_CLI::get_runner()->config['path'] )
			);
			if ( $result->return_code ) {
				Functions::rrmdir( $tmp_dir );
				WP_CLI::error( sprintf( "Can't import database from '%s'.", $args[0] ) );
			}
		}

		Functions::rrmdir( $tmp_dir );

		WP_CLI::success( sprintf( "Restored from '%s'.", $args[0] ) );
	}

	/**
	 * Launch WordPress from backup file with PHP built-in web server.
	 *
	 * ## Options
	 *
	 * <file>
	 * : The path to the backup file.
	 *
	 * [--dbuser=<dbuser>]
	 * : The database user.
	 *
	 * [--dbpass=<dbpass>]
	 * : The database password.
	 *
	 * [--dbname=<dbname>]
	 * : The database name.
	 *
	 * [--dbhost=<dbhost>]
	 * : Set the database host.
	 * ---
	 * default: localhost
	 * ---
	 *
	 * @when before_wp_load
	 */
	function server( $args, $assoc_args )
	{
		if ( ! is_file( $args[0] ) ) {
			WP_CLI::error( "No such file or directory." );
		}

		$db = Functions::get_db_config( $assoc_args );

		Functions::unzip( $args[0], getcwd() );

		$json = file_get_contents( getcwd() . '/manifest.json' );
		$manifest = json_decode( $json, true );

		$result = WP_CLI::launch_self(
			"core download",
			array(),
			array(
				'version' => $manifest['wp_version'],
				'path' => getcwd() . '/wordpress',
				'force' => true,
			),
			false,
			true,
			array( 'path' => getcwd() . '/wordpress' )
		);

		if ( 0 !== $result->return_code ) {
			WP_CLI::error( $result->stderr );
		}

		$result = WP_CLI::launch_self(
			"core config",
			array(),
			array(
				'dbname' => $db['dbname'],
				'dbuser' => $db['dbuser'],
				'dbpass' => $db['dbpass'],
				'dbhost' => 'localhost',
				'dbprefix' => $manifest['db_prefix'],
				'force' => true,
			),
			false,
			true,
			array( 'path' => getcwd() . '/wordpress' )
		);

		if ( 0 === $result->return_code ) {
			rename( getcwd() . '/wordpress/wp-config.php', getcwd() . '/wp-config.php' );
		} else {
			WP_CLI::error( $result->stderr );
		}

		$result = WP_CLI::launch_self(
			"db create",
			array(),
			array(),
			false,
			true,
			array( 'path' => getcwd() . '/wordpress' )
		);

		$result = WP_CLI::launch_self(
			"db check",
			array(),
			array(),
			false,
			true,
			array( 'path' => getcwd() . '/wordpress' )
		);

		if ( 0 !== $result->return_code ) {
			WP_CLI::error( $result->stderr );
		}

		if ( ! empty( $manifest['locale'] ) ) {
			$result = WP_CLI::launch_self(
				"core language install",
				array( $manifest['locale'] ),
				array(),
				false,
				true,
				array( 'path' => getcwd() . '/wordpress' )
			);
		}

		if ( 0 !== $result->return_code ) {
			WP_CLI::error( $result->stderr );
		}

		$result = WP_CLI::launch_self(
			"db import",
			array( getcwd() . '/wordpress.sql' ),
			array(),
			false,
			true,
			array( 'path' => getcwd() . '/wordpress' )
		);

		if ( 0 !== $result->return_code ) {
			WP_CLI::error( $result->stderr );
		}

		$result = WP_CLI::launch_self(
			"search-replace",
			array( $manifest['home_url'], "http://localhost:8080" ),
			array(),
			false,
			true,
			array( 'path' => getcwd() . '/wordpress' )
		);

		if ( 0 !== $result->return_code ) {
			WP_CLI::error( $result->stderr );
		}

		$result = WP_CLI::run_command(
			array( "server" ),
			array(
				'docroot' => getcwd() . '/wordpress'
			)
		);
	}
}

WP_CLI::add_command( 'vackup', 'Vackup\CLI' );
