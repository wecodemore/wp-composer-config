<?php

namespace WCM\WPComposerConfig;

require __DIR__.'/../vendor/autoload.php';

use Composer\Script\Event;
use Composer\IO\IOInterface;
use GuzzleHttp\Client;

class WPConfigCommand
{
	private static $target;

	private static $io;

	private static $env;

	private static $source;

	private static $sections = array(
		'Database Creds & Settings' => array(
			'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST',
			'DB_CHARSET', 'DB_COLLATE',
			'WP_ALLOW_REPAIR',
			'CUSTOM_USER_TABLE', 'CUSTOM_USER_META_TABLE',
		),
		'Debugging' => array(
			'WP_DEBUG', 'WP_DEBUG_LOG', 'WP_DEBUG_DISPLAY',
			'SAVEQUERIES', 'DIEONDBERROR', 'ERRORLOGFILE',
		),
		'Language Settings' => array(
			'WPLANG', 'WP_LANG_DIR', 'LANGDIR',
		),
		'Paths & URls' => array(
			'WP_SITEURL', 'WP_HOME',
			'WP_CONTENT_DIR', 'WP_CONTENT_URL',
			'WP_PLUGIN_DIR', 'WP_PLUGIN_URL', 'WPMU_PLUGIN_DIR', 'WPMU_PLUGIN_URL',
			'WP_TEMP_DIR',
		),
		'Update Settings' => array(
			'CORE_UPGRADE_SKIP_NEW_BUNDLED',
		),
		'Post Settings' => array(
			'AUTOSAVE_INTERVAL', 'EMPTY_TRASH_DAYS', 'WP_POST_REVISIONS',
		),
		'Media Settings' => array(
			'MEDIA_TRASH', 'IMAGE_EDIT_OVERWRITE',
		),
		'Cron Settings' => array(
			'DISABLE_WP_CRON', 'WP_CRON_LOCK_TIMEOUT',
		),
		'Security Settings' => array(
			'DISALLOW_FILE_EDIT', 'DISALLOW_FILE_MODS',
			'DISALLOW_UNFILTERED_HTML', 'ALLOW_UNFILTERED_UPLOADS',
			'FORCE_SSL_LOGIN', 'FORCE_SSL_ADMIN',
		),
		'Mail Settings' => array(
			'WP_MAIL_INTERVAL',
		),
		'Default Theme' => array(
			'WP_DEFAULT_THEME',
		),
		'FTP Settings' => array(
			'FTP_HOST', 'FTP_USER', 'FTP_PASS',
			'FTP_SSH', 'FTP_SSL', 'FTP_PRIKEY', 'FTP_PUBKEY',
			'FTP_BASE', 'FTP_CONTENT_DIR', 'FTP_PLUGIN_DIR', 'FTP_LANG_DIR',
		),
		'Filesystem Settings' => array(
			'FS_CHMOD_DIR', 'FS_CHMOD_FILE',
			'FS_METHOD', 'FS_TIMEOUT', 'FS_CONNECT_TIMEOUT',
		),
		'Proxy Settings' => array(
			'WP_PROXY_HOST', 'WP_PROXY_PORT', 'WP_PROXY_USERNAME', 'WP_PROXY_PASSWORD',
			'WP_PROXY_BYPASS_HOSTS', 'WP_ACCESSIBLE_HOSTS', 'WP_HTTP_BLOCK_EXTERNAL',
		),
		'Cookie Settings' => array(
			'COOKIEHASH',
			'PASS_COOKIE', 'LOGGED_IN_COOKIE', 'AUTH_COOKIE', 'SECURE_AUTH_COOKIE',
			'USER_COOKIE', 'TEST_COOKIE',
			'COOKIE_DOMAIN',
			'COOKIEPATH', 'SITECOOKIEPATH', 'ADMIN_COOKIE_PATH', 'PLUGINS_COOKIE_PATH',
		),
		'Memory Settings' => array(
			'WP_MEMORY_LIMIT', 'WP_MAX_MEMORY_LIMIT',
		),
		'Performance Settings' => array(
			'COMPRESS_CSS', 'COMPRESS_SCRIPTS', 'CONCATENATE_SCRIPTS', 'ENFORCE_GZIP',
			'SCRIPT_DEBUG',
		)
	);

	/**
	 * @param Event $event
	 * @return bool
	 */
	public static function postPackageInstall( Event $event = null )
	{
		$package = $event
			->getComposer()
			->getPackage();
		self::$io = $event
			->getComposer()
			->getIO();
		$extra = $package->getExtra();

		if ( ! isset( $extra['wordpress-install-dir'] ) )
		{
			self::$io->write( ' |- You need to define the WP install dir in \"extra\" : { ... }. Aborting.' );
			return false;
		}

		self::$target = getcwd()."/{$extra['wordpress-install-dir']}/wp-config.php";

		self::setEnv( getcwd()."/{$extra['wordpress-env-dir']}" );
		self::$source = file_get_contents( self::$target );

		self::addHeader();
		self::append( 'Header', array( sprintf(
			"Dotenv::load( __DIR__.'/../%s' );",
			$extra['wordpress-env-dir']
		) ) );
		self::addAbspath();

		foreach ( self::$sections as $section => $constants )
		{
			if ( self::$io->askConfirmation( " |- Do you want to add {$section} [Y/n]? ", false ) )
				self::addConstants( $section, $constants );
		}

		self::append( 'Database Credentials & Settings', array(
			"\n\n".'$GLOBALS[\'table_prefix\'] = getenv( \'DB_TABLE_PREFIX\' );',
		) );

		self::addSalt();

		self::$io->write( ' `- Done. wp-config.php successfully added.' );

		return true;
	}

	/**
	 * Fetch the .env file contents
	 * to use it as a blueprint for the `wp-config.php` generation.
	 * Filter comments and non-setters to align with vlucas/phpdotenv
	 * @param string $location
	 */
	public static function setEnv( $location )
	{
		$contents = file(
			$location,
			FILE_IGNORE_NEW_LINES
			| FILE_SKIP_EMPTY_LINES
		);

		foreach ( $contents as $line )
		{
			// Ignore comments
			if ( 0 === strpos( trim( $line ), '#' ) )
				continue;

			// Lines without `=`/non-setters get removed from the stack
			if ( ! is_int( strpos( $line, '=' ) ) )
			{
				// Loop through sections and unset if found
				foreach ( self::$sections as $section => $constants )
				{
					$key = array_search( $line, $constants, true );
					if ( is_int( $key ) )
					{
						unset( self::$sections[ $section ][ $key ] );
						break;
					}
				}
				continue;
			}

			// If we got until here, we can set it
			$line = explode( '=', $line );
			self::$env[ $line[0] ] = $line[1];
		}
	}

	public static function addHeader()
	{
		self::append( 'Header', array(
			"<?php\n# ==== WordPress Configuration =====\n",
			"# SHORTS",
			"define( 'DS', DIRECTORY_SEPARATOR );",
			"define( 'PS', PATH_SEPARATOR );",
		) );
	}

	public static function addAbspath()
	{
		self::append( 'Path', array(
			"# Absolute path to the WordPress directory.",
			"if ( ! defined( 'ABSPATH' ) )",
			"\tdefine( 'ABSPATH', dirname( __FILE__ ).DS );",
		) );
	}

	public static function addSalt()
	{
		$salt = self::fetchSalt();
		if ( ! $salt )
			self::$io->write( ' |- WordPress Remote API for Salt generation did not respond' );

		if ( false === strpos( self::$source, 'AUTH_KEY' ) )
		{
			self::append( 'Auth Keys', $salt );
			self::$io->write( ' |- Salt & Auth keys generated and added.' );
		}
	}

	/**
	 * Performs the remote request to the Secret Salt API on wp.org
	 * @return string The PHP needed to set/define the keys incl. generated values
	 */
	public static function fetchSalt()
	{
		$client = new Client();
		$response = $client->get( 'api.wordpress.org/secret-key/1.1/salt/' );

		return (
			200 === abs( intval( $response->getStatusCode() ) )
			AND "OK" === $response->getReasonPhrase()
			AND "text/plain;charset=utf-8" === $response->getHeader( 'content-type' )
		)
			? $response
				->getBody()
				->getContents()
			: false;
	}

	public static function addConstants( $section, $constants )
	{
		$append = array();
		$append[] = "\n# {$section}";
		if ( is_array( $constants ) )
		{
			foreach ( $constants as $c )
			{
				// Do not append in case
				false === strpos( self::$source, $c )
					AND $append[] = "define( '{$c}', getenv( '{$c}' ) );";
			}
		}
		if ( 1 >= count( $append ) )
			return;

		self::append( $section, $append );
	}

	public static function append( $task, $content )
	{
		is_array( $content )
			AND $content = join( "\n", $content );

		if ( false === strpos( self::$source, $content ) )
		{
			$result = file_put_contents(
				self::$target,
				"{$content}\n\n",
				FILE_APPEND
			);
			self::isSuccess( $task, $result );
		}

	}

	/**
	 * Check if the file write process was successful, add not if not
	 * @param string $task
	 * @param string $result
	 */
	public static function isSuccess( $task, $result )
	{
		$note = ! is_int( $result )
			? ' |- Could not write %s to `wp-config.php`'
			: ' |- Successfully added %s';

		self::$io->write( sprintf( $note, $task ) );
	}

	/**
	 * Check if the file in the root dir exists. Prompt for a new one if it does not exist.
	 * @param IOInterface $io
	 * @param string      $dir
	 * @return mixed
	 */
	public static function getDir( IOInterface $io, $dir )
	{
		if ( ! is_dir( getcwd()."/{$dir}" ) )
		{
			$io->write( sprintf(
				' |- The specified WP root location %s does not exist.',
				$dir
			) );
			$dir = $io->ask( ' |- Please provide the root directory of WordPress: ', $dir );
			return self::getDir( $io, $dir );
		}

		return $dir;
	}
}