<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * ADBC files class.
 * 
 * This class provides files and folders methods.
 */
class ADBC_Files extends ADBC_Singleton {

	/**
	 * The WordPress file system.
	 *
	 * @var object|null
	 */
	private $wp_fs = null;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		parent::__construct();
		$this->prepare_wp_fs();
	}

	/**
	 * Prepare the WordPress file system.
	 */
	private function prepare_wp_fs() {

		// Load the WP file API if needed.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Initialize the global filesystem instance if it is not already initialized.
		if ( empty( $GLOBALS['wp_filesystem'] ) ) {
			WP_Filesystem();
		}

		$fs = ! empty( $GLOBALS['wp_filesystem'] ) ? $GLOBALS['wp_filesystem'] : null;

		// If FTPext is selected in the FS_METHOD but fails in connection, fall back to direct Filesystem.
		// This avoids Fatal error: ftp_pwd(): Argument #1 ($ftp) must be of type FTP\Connection, null given
		if ( $fs instanceof WP_Filesystem_FTPext && empty( $fs->link ) ) {

			if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
			}

			$fs = new WP_Filesystem_Direct( null );
		}

		$this->wp_fs = $fs;

		// guarantee FS_CHMOD_* constants exist (important for PHP 8+).

		if ( ! defined( 'FS_CHMOD_DIR' ) ) {
			$dir_perms = @fileperms( ABSPATH );
			if ( $dir_perms ) {
				define( 'FS_CHMOD_DIR', ( $dir_perms & 0777 ) | 0755 );
			} else {
				// Safe fallback if fileperms() fails.
				define( 'FS_CHMOD_DIR', 0755 );
			}
		}

		if ( ! defined( 'FS_CHMOD_FILE' ) ) {
			$file_perms = @fileperms( ABSPATH . 'index.php' );
			if ( $file_perms ) {
				define( 'FS_CHMOD_FILE', ( $file_perms & 0777 ) | 0644 );
			} else {
				// Safe fallback if fileperms() fails.
				define( 'FS_CHMOD_FILE', 0644 );
			}
		}

	}

	/**
	 * Check if the WordPress file system is initialized.
	 * 
	 * @return boolean True if the WordPress file system is initialized, false otherwise.
	 */
	public function is_wp_fs_initialized() {

		if ( $this->wp_fs === null ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the handle of a file.
	 * 
	 * @param string $file_path The file path.
	 * @param string $mode The mode to open the file in. Default is 'r'.
	 * @return resource|bool The file handle or false if the file does not exist or is not readable.
	 */
	public function get_file_handle( $file_path, $mode = 'r' ) {

		if ( ! $this->is_wp_fs_initialized() )
			return false;

		if ( $mode == 'r' && ( ! $this->exists( $file_path ) || ! $this->is_readable( $file_path ) ) )
			return false;

		$handle = fopen( $file_path, $mode );

		return $handle;
	}

	/**
	 * Check if a file exists.
	 * 
	 * @return boolean True if the file exists, false otherwise.
	 */
	public function exists( $file_path ) {

		if ( ! $this->is_wp_fs_initialized() )
			return false;

		return $this->wp_fs->exists( $file_path );

	}

	/**
	 * Check if a file is readable.
	 * 
	 * @return boolean True if the file is readable, false otherwise.
	 */
	public function is_readable( $file_path ) {

		if ( ! $this->is_wp_fs_initialized() )
			return false;

		return $this->wp_fs->is_readable( $file_path );

	}

	/**
	 * Check if a file is writable.
	 * 
	 * @return boolean True if the file is writable, false otherwise.
	 */
	public function is_writable( $file_path ) {

		if ( ! $this->is_wp_fs_initialized() )
			return false;

		return $this->wp_fs->is_writable( $file_path );

	}

	/**
	 * Check if a file is readable and writable.
	 * 
	 * @return boolean True if the file is readable and writable, false otherwise.
	 */
	public function is_readable_and_writable( $file_path ) {

		if ( ! $this->is_wp_fs_initialized() )
			return false;

		return $this->is_readable( $file_path ) && $this->is_writable( $file_path );

	}

	/**
	 * Check if a path is a directory.
	 * 
	 * @return boolean True if the path is a directory, false otherwise.
	 */
	public function is_dir( $folder_path ) {

		if ( ! $this->is_wp_fs_initialized() )
			return false;

		return $this->wp_fs->is_dir( $folder_path );

	}

	/**
	 * Check if a path is a file.
	 * 
	 * @param string $file_path The file path.
	 * @return boolean True if the path is a file, false otherwise.
	 */
	public function is_file( $file_path ) {

		if ( ! $this->is_wp_fs_initialized() )
			return false;

		return $this->wp_fs->is_file( $file_path );

	}

	/**
	 * Get the file size.
	 * 
	 * @return int|bool The file size or false in case of failure.
	 */
	public function size( $file_path ) {

		if ( ! $this->is_wp_fs_initialized() )
			return false;

		return $this->wp_fs->size( $file_path );

	}

	/**
	 * Get the contents of a file.
	 * 
	 * @param string $file_path The file path.
	 * @return string|bool The file content or false in case of failure.
	 */
	public function get_contents( $file_path ) {

		if ( ! $this->is_wp_fs_initialized() )
			return false;

		return $this->wp_fs->get_contents( $file_path );

	}

	/**
	 * Create an new folder if it doesn't exist.
	 *
	 * @param string $folder_path Folder path to create.
	 * @return boolean True if successful, false otherwise.
	 */
	public function create_folder( $folder_path, $secure_folder = false ) {

		if ( ! $this->is_wp_fs_initialized() )
			return false;

		// Create the directory only if it doesn't exist.
		if ( ! $this->is_dir( $folder_path ) ) {
			if ( ! $this->wp_fs->mkdir( $folder_path ) )
				return false;
		}

		// Always try to secure the folder if needed.
		if ( $secure_folder )
			$this->secure_folder( $folder_path );

		return true;

	}

	/**
	 * Create an new empty file if it doesn't exist.
	 *
	 * @param string $file_path The file path.
	 * @return boolean True if successful, false otherwise.
	 */
	public function create_file( $file_path ) {

		if ( ! $this->is_wp_fs_initialized() )
			return false;

		// Check if the file already exists.
		if ( $this->exists( $file_path ) )
			return true;

		// Create the file.
		if ( ! $this->put_contents( $file_path, "" ) )
			return false;

		return true;
	}

	/**
	 * Put contents into a file or create a new file if it doesn't exist.
	 * 
	 * @param string $file_path The file path.
	 * @param string $content The content to put into the file.
	 * @return boolean True if successful, false otherwise.
	 */
	public function put_contents( $file_path, $content ) {

		if ( ! $this->is_wp_fs_initialized() )
			return false;

		return $this->wp_fs->put_contents( $file_path, $content );

	}

	/**
	 * Create a silence is golden file or .htaccess or all.
	 *
	 * @param string $folder_path Folder path to create the file in.
	 * @param string $file_type The file type to create ('index', 'htaccess', 'all').
	 * @return boolean True if successful, false otherwise.
	 */
	public function secure_folder( $folder_path, $file_type = 'all' ) {

		if ( ! $this->is_wp_fs_initialized() )
			return false;

		// Check if the file type is valid.
		if ( ! in_array( $file_type, [ 'index', 'htaccess', 'all' ] ) )
			return false;

		// Create the index.php file if it doesn't exist.
		if ( in_array( $file_type, [ 'index', 'all' ] ) ) {
			if ( ! $this->exists( $folder_path . '/index.php' ) ) {
				$index_content = "<?php\n// Silence is golden.";
				if ( ! $this->put_contents( $folder_path . '/index.php', $index_content ) )
					return false;
			}
		}

		// Create the .htaccess file if it doesn't exist.
		if ( in_array( $file_type, [ 'htaccess', 'all' ] ) ) {
			if ( ! $this->exists( $folder_path . '/.htaccess' ) ) {
				$htaccess_content = "Order deny,allow\nDeny from all";
				if ( ! $this->put_contents( $folder_path . '/.htaccess', $htaccess_content ) )
					return false;
			}
		}

		return true;
	}

	/**
	 * Delete folder with its content.
	 *
	 * @param string $folder_path Folder path to delete.
	 * @return boolean True if successful, false otherwise.
	 */
	public function delete_folder( $folder_path ) {

		if ( ! $this->is_wp_fs_initialized() )
			return false;

		if ( ! $this->exists( $folder_path ) ) {
			return false;
		}

		if ( ! $this->is_dir( $folder_path ) ) {
			return false;
		}

		$deleted = $this->wp_fs->delete( $folder_path, true );

		if ( ! $deleted ) {
			return false;
		}

		return true;

	}


	/**
	 * Get the list of folders names inside a directory.
	 *
	 * @param string $dir_path The directory path.
	 * @param boolean $alph_sorted Whether to sort the folders alphabetically or not.
	 * @return string[] The list of folders names inside the directory.
	 */
	public function get_list_of_dirs_inside_dir( $dir_path, $alph_sorted = false ) {

		if ( ! $this->is_wp_fs_initialized() )
			return [];

		if ( ! $this->is_dir( $dir_path ) )
			return [];

		$dirs = $this->wp_fs->dirlist( $dir_path );

		if ( ! is_array( $dirs ) )
			return [];

		$dirs_list = [];

		foreach ( $dirs as $dir ) {
			if ( $dir['type'] === 'd' ) {
				$dirs_list[] = $dir['name'];
			}
		}

		if ( $alph_sorted )
			sort( $dirs_list );

		return $dirs_list;

	}


	/**
	 * Get the list of files names inside a directory.
	 *
	 * @param string $dir_path The directory path.
	 * @param boolean $alph_sorted Whether to sort the files alphabetically or not.
	 * @return string[] The list of files names inside the directory.
	 */
	public function get_list_of_files_inside_dir( $dir_path, $alph_sorted = false ) {

		if ( ! $this->is_wp_fs_initialized() )
			return [];

		if ( ! $this->is_dir( $dir_path ) )
			return [];

		$files = $this->wp_fs->dirlist( $dir_path );

		if ( ! is_array( $files ) )
			return [];

		$files_list = [];

		foreach ( $files as $file ) {
			if ( $file['type'] === 'f' ) {
				$files_list[] = $file['name'];
			}
		}

		if ( $alph_sorted )
			sort( $files_list );

		return $files_list;

	}

}