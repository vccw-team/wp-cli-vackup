<?php

class Vackup_Functions
{
	/**
	 * Create an archive.
	 *
	 * @param  array $args The `$args` for the WP-CLI.
	 * @param  array $assoc_args The `$assoc_args` for the WP-CLI.
	 * @return string The path to archive.
	 */
	public static function create_archive( $args, $assoc_args )
	{
		$excludes = array(
			'wp-config.php',
			'wp-config-sample.php',
		);

		$tmp_dir = self::tempdir( 'VAK' );
		self::rcopy( ABSPATH, $tmp_dir . '/wordpress', $excludes );

		WP_CLI::launch_self(
			"db export",
			array( $tmp_dir . "/wordpress.sql" ),
			array(),
			true,
			true,
			array( 'path' => WP_CLI::get_runner()->config['path'] )
		);

		$filename = preg_replace( "#^https?://#", "", home_url() );
		$filename = preg_replace( "#[^A-Za-z0-9-_\.\-]#", "-", $filename );
		$filename = $filename . '-' . date( 'YmdHis' ) . '.zip';

		if ( empty( $assoc_args['dir'] ) ) {
			$archive = untrailingslashit( getcwd() ) . "/" . $filename;
		} else {
			$home = getenv( 'HOME' );
			if ( !$home ) {
				// sometime in windows $HOME is not defined
				$home = getenv( 'HOMEDRIVE' ) . getenv( 'HOMEPATH' );
			}
			$dir = preg_replace( "#~#", untrailingslashit( $home ), $assoc_args['dir'] );
			$archive = untrailingslashit( $dir ) . "/" . $filename;
		}

		$file = self::zip( $tmp_dir, $archive );

		self::rrmdir( $tmp_dir );
		if ( is_wp_error( $file ) ) {
			WP_CLI::error( $file->get_error_message() );
		}

		return $file;
	}

	/**
	 * Remove a directory recursively.
	 *
	 * @param  string $dir Path to the directory you want to remove.
	 * @return void
	 */
	public static function rrmdir( $dir )
	{
		self::rempty( $dir );

		rmdir( $dir );
	}

	/**
	 * Empty a directory recursively.
	 *
	 * @param  string $dir     Path to the directory you want to remove.
	 * @param  array  $exclude An array of the files to exclude.
	 * @return void
	 */
	public static function rempty( $dir, $excludes = array() )
	{
		$dir = untrailingslashit( $dir );

		$files = self::get_files( $dir, RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $files as $fileinfo ) {
			if ( $fileinfo->isDir() ) {
				$skip = false;
				foreach ( $excludes as $exclude ) {
					if ( 0 === strpos( $exclude, $files->getSubPathName() ) ) {
						$skip = true;
					}
				}
				if ( ! $skip ) {
					rmdir( $fileinfo->getRealPath() );
				}
			} else {
				if ( ! in_array( $files->getSubPathName(), $excludes ) ) {
					unlink( $fileinfo->getRealPath() );
				}
			}
		}
	}

	/**
	 * Create a temporary working directory
	 *
	 * @param  string $prefix Prefix for the temporary directory you want to create.
	 * @return string         Path to the temporary directory.
	 */
	public static function tempdir( $prefix = '' )
	{
		$tempfile = tempnam( sys_get_temp_dir(), $prefix );
		if ( file_exists( $tempfile ) ) {
			unlink( $tempfile );
		}
		mkdir( $tempfile );
		if ( is_dir( $tempfile ) ) {
			return $tempfile;
		}
	}

	/**
	 * Copy directory recursively.
	 *
	 * @param  string $source  Path to the source directory.
	 * @param  string $dest    Path to the destination.
	 * @param  array  $exclude An array of the files to exclude.
	 * @return void
	 */
	public static function rcopy( $src, $dest, $exclude = array() )
	{
		$src = untrailingslashit( $src );
		$dest = untrailingslashit( $dest );

		if ( ! is_dir( $dest ) ) {
			mkdir( $dest, 0755 );
		}

		$iterator = self::get_files( $src );
		foreach ( $iterator as $item ) {
			if ( $item->isDir() ) {
				if ( ! is_dir( $dest . '/' . $iterator->getSubPathName() ) ) {
					mkdir( $dest . '/' . $iterator->getSubPathName() );
				}
			} else {
				if ( ! in_array( $iterator->getSubPathName(), $exclude ) ) {
					copy( $item, $dest . '/' . $iterator->getSubPathName() );
				}
			}
		}
	}

	/**
	 * Create a zip archive from $source to $destination.
	 *
	 * @param string $source Path to the source directory.
	 * @param string $dest   Path to the .zip file.
	 * @return string        Path to the .zip file or WP_Error object.
	 */
	public static function zip( $src, $destination )
	{
		$src = untrailingslashit( $src );

		if ( ! is_dir( $src ) ) {
			return new WP_Error( "error", "No such file or directory." );
		}

		if ( ! is_dir( dirname( $destination ) ) ) {
			return new WP_Error( "error", "No such file or directory." );
		}

		if ( ! extension_loaded( 'zip' ) || ! file_exists( $src ) ) {
			return new WP_Error( "error", "PHP Zip extension is not installed. Please install it." );
		}

		$destination = realpath( dirname( $destination ) ) . "/" . basename( $destination );

		$zip = new ZipArchive();
		if ( ! $zip->open( $destination, ZIPARCHIVE::CREATE ) ) {
			return new WP_Error( "error", "No such file or directory." );
		}

		$iterator = self::get_files( $src );

		foreach ( $iterator as $item ) {
			if ( $item->isDir() ) {
				$zip->addEmptyDir( str_replace( $src . '/', '', $item . '/' ) );
			} else {
				$zip->addFromString( str_replace( $src . '/', '', $item ), file_get_contents( $item ) );
			}
		}

		$zip->close();

		if ( ! is_file( $destination ) ) {
			return new WP_Error( "error", "No such file or directory." );
		}

		return $destination;
	}

	/**
	 * Unzip
	 *
	 * @param string $src  Path to the .zip archive.
	 * @param string $dest Path to extract .zip.
	 * @return string      `true` or WP_Error object.
	 */
	public static function unzip( $src, $dest )
	{
		if ( ! is_file( $src ) ) {
			return new WP_Error( "No such file or directory." );
		}

		$zip = new ZipArchive;
		$res = $zip->open( $src );
		if ( true === $res ) {
			// extract it to the path we determined above
			$zip->extractTo( $dest );
			$zip->close();
			return true;
		}

		return new WP_Error( "Can not open {$src}." );
	}

	/**
	 * Get file's iterator object from the directory.
	 *
	 * @param string $dir   Path to the directory.
	 * @param string $flags Flags for the `RecursiveIteratorIterator()`.
	 * @return string       Literator object of the `RecursiveIteratorIterator()`.
	 */
	public static function get_files( $dir, $flags = RecursiveIteratorIterator::SELF_FIRST )
	{
		$dir = untrailingslashit( $dir );

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			$flags
		);

		return $iterator;
	}
}
