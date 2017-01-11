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
	 * Create a .zip archive from your WordPress.
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
		$res = Functions::create_archive( $args, $assoc_args );
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
}

WP_CLI::add_command( 'vackup', 'Vackup\CLI' );
