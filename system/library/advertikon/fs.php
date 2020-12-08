<?php
/**
 * Advertikon File system Class
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

class Fs {

	/**
	 * Try to construct file path up from the root
	 * If fails - returns FALSE
	 * @param String $file File path
	 * @return Boolean|String
	 */
	public function plant_file( $file ) {

		if( 'string' !== gettype( $file ) ) {
			return false;
		}

		$base = dirname( DIR_SYSTEM );
		$file_path = $file;

		// Windows
		if( substr( PHP_OS, 0, 3 ) === 'WIN' ) {

			// Find driver letter for file path (if applied)
			if( preg_match( '/^([a-zA-Z]:\\\\?|(\\\\){2})/', $file, $m ) ) {
				$dir_letter = $m[0];
				$file = substr( $file, strlen( $dir_letter ) );
			}

			// Find driver letter for store base directory
			if( preg_match( '/^([a-zA-Z]:\\\\?|(\\\\){2})/', $base, $m ) ) {
				$dir_base_letter = $m[0];
				$base = substr( $base, strlen( $dir_base_letter ) );

			} else {
				$dir_base_letter = '\\\\';
			}

			// File specified from the root explicitly
			if( preg_match( '/^([a-zA-Z]:\\\\|(\\\\){2})/', $file_path, $m ) ) {

				$same_dir_letter = strtolower( $dir_letter ) === strtolower( $dir_base_letter );

				// Same driver letters or one file/base has UNC path format
				if( $same_dir_letter || $dir_base_letter === '\\\\' || $dir_letter === '\\\\' ) {
					if( strpos( strtolower( $file ), strtolower( $base ) ) === 0 ) {

						return $dir_base_letter . $file;
					} else {

						return false;
					}

				} else {

					return false;
				}
			}

		// Right OS
		} else {
			$dir_base_letter = '/';
		}

		$ds = DIRECTORY_SEPARATOR;
		$base_parts = explode( $ds, trim( $base, $ds ) );

		// Remove all "dot" relative components
		$file = preg_replace( '~' . preg_quote( $ds ) . '\.(?=$|' . preg_quote( $ds ) . ')~', '', $file );
		$file_parts = explode( $ds, trim( $file, $ds ) );
		$in = false;

		// Handle "base part" of the path
		foreach( $base_parts as $part ) {

			// Path is below root folder
			if ( ! $file_parts ) {
				return false;
			}

			// We got '/' which is ambiguous or something 'foo//bar' which is error
			if ( '' === $file_parts[0] ) {
				return false;
			}

			// Remove all common parts
			if( $part === current( $file_parts ) ) {
				array_shift( $file_parts );
				$in = true;

			// Path deviates
			} elseif ( $in ) {
				return false;
			}
		}

		// Handle the rest
		foreach( $file_parts as $part ) {
			if ( '..' === $part ) {
				array_pop( $base_parts );

			} else {
				$base_parts[] = $part;
			}
		}

		// Construct full name
		$path = $dir_base_letter . implode( $ds, $base_parts );

		// Check if path above the root folder
		if ( strpos( $path, str_replace( '/', $ds, dirname( DIR_SYSTEM ) ) ) === 0 ) {
			return $path;
		}

		return false;
	}

	/**
	 * Checks whether file is above the store root
	 * @param String $path 
	 * @return Boolean|Null
	 */
	public function above_store_root( $path ) {
		return  $this->plant_file( $path );
	}

	/**
	 * Recursively creates directory
	 * @param string $path 
	 * @param int $mode Newly created directories permissions 
	 * @return boolean Operation result
	 */
	public function mkdir( $path, $mode = 0755 ) {
		$path = $this->plant_file( $path );

		if( false === $path ) {
			return $path;
		}

		$path = trim( substr( $path, strlen( dirname( DIR_SYSTEM ) ) ), DIRECTORY_SEPARATOR );
		$current_path = dirname( DIR_SYSTEM ) . '/';
		$created = array();

		try {
			foreach( explode( DIRECTORY_SEPARATOR, $path ) as $part ) {
				if( !is_dir( $current_path . $part ) ) {
					if( @!mkdir( $current_path . $part ) ) {
						throw new Exception( sprintf( 'Failed create folder %s', $current_path .$part ) );
					}

					if( !@chmod( $current_path . $part, $mode ) ) {
						throw new Exception( sprintf( "Failed to change mode of folder %s to %o", $current_path . $part, $mode ) );
					}

					$created[] = $current_path . $part;
				}

				$current_path .= $part . '/';
			}

		} catch ( Exception $e ) {
			ADK()->log( $e );

			foreach( $created as $dir ) {
				if( is_dir( $dir ) ) {
					@rmdir( $dir );
				}
			}

			return false;
		}

		return true;
	}

	/**
	 * Recursively deletes folder with its content 
	 * @param string $dir Directory name 
	 * @return void
	 */
	public function rmdir( $dir ) {
		if ( is_file( $dir ) ) {
			unlink( $dir );

		} elseif ( is_dir( $dir ) ) {
			$dir = rtrim( $dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;

			foreach( scandir( $dir ) as $item ) {
				if ( '.' === $item || '..' === $item ) {
					continue;
				}

				$this->rmdir( $dir . $item );
			}
			
			rmdir( $dir );
		}
	}

	/**
	 * Iterates over directory structure and fires callback each time file or directory encountered
	 * @param string $dir Directory name
	 * @param function Iterator callback
	 * @param boolean $all Flag, to collect hidden and temporary
	 * @param boolean $only_files Flag to iterate over files only
	 * @return array
	 */
	public function iterate_directory( $dir, $callback, $all = false, $only_files = false ) {
		if ( !$all && ( '.' === substr( $dir, 0 , 1 ) || '~' === substr( $dir, 0, 1 ) ) ) return;

		if ( is_file( $dir ) ) {
			call_user_func( $callback, $dir );

		} elseif ( is_dir( $dir ) ) {
			$dir = rtrim( $dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;

			foreach( scandir( $dir ) as $item ) {
				if ( '.' === $item || '..' === $item ) {
					continue;
				}

				if ( !$all && ( '.' === substr( $item, 0 , 1 ) || '~' === substr( $item, 0, 1 ) ) )
					continue;

				$this->iterate_directory( $dir . $item, $callback, $all, $only_files );
			}

			if ( !$only_files ) {
				call_user_func( $callback, $dir );
			}
		}
	}

	/**
	 * Returns directory's content size
	 * @param string $dir Directory path
	 * @return integer
	 */
	public function get_dir_size( $dir ) {
		$fir = (string)$dir;
		$totalSize = null;
		$count = 0;

		if ( is_dir( $dir ) ) {
			$os = strtoupper( substr( PHP_OS, 0, 3 ) );

			// Windows
			if ( $os === 'WIN' ) {
				if ( extension_loaded( 'com_dotnet' ) ) {
					$obj = new \COM( 'scripting.filesystemobject' );

					if ( is_object( $obj ) ) {
						$ref = $obj->getfolder($dir);
						$totalSize = $ref->size;
						$obj = null;
					}
				}
			}

			// Real OS
			if ( is_null( $totalSize ) && $os !== 'WIN' && extension_loaded( 'popen') ) {
				$io = popen('du -sb ' . $dir, 'r');

				if ( $io !== false ) {
					$totalSize = intval( fgets( $io, 80 ) );
					pclose( $io );
				}
			}

			if ( is_null( $totalSize ) ) {
				$totalSize = 0;
				$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir ) );

				foreach ( $files as $file ) {
					$totalSize += $file->getSize();
					$count++;
				}
			}

		} else if (is_file( $dir ) ) {
			$totalSize = filesize( $dir );
		}

		return $totalSize;
	}

	/**
	 * Returns fa-icon, which corresponds to specific mine type
	 * @since 1.1.0
	 * @param string mime Mime type
	 * @return string
	 */
	public function get_mime_icon( $mime ) {
		$icon = null;

		switch( $mime ) {
		case "application/msword":
		case "application/vnd.ms-word.document.macroenabled.12" :
		case "application/vnd.ms-word.template.macroenabled.12" :
		case "application/vnd.openxmlformats-officedocument.wordprocessingml.document" :
		case "application/vnd.openxmlformats-officedocument.wordprocessingml.template" :
			$icon = "file-word-o";
			break;
		case "application/rtf" :
			$icon = "fle-text";
			break;
		case "application/pdf" :
			$icon = "file-pdf-o";
			break;
		case "application/zip" :
			$icon = "file-zip-o";
			break;
		case "application/vnd.ms-excel" :
		case "application/vnd.ms-excel.addin.macroenabled.12" :
		case "application/vnd.ms-excel.sheet.binary.macroenabled.12" :
		case "application/vnd.ms-excel.sheet.macroenabled.12" :
		case "application/vnd.ms-excel.template.macroenabled.12" :
		case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" :
		case "application/vnd.openxmlformats-officedocument.spreadsheetml.template" :
			$icon = "file-excel-o";
			break;
		case "application/vnd.ms-powerpoint" :
		case "application/vnd.ms-powerpoint.addin.macroenabled.12" :
		case "application/vnd.ms-powerpoint.presentation.macroenabled.12" :
		case "application/vnd.ms-powerpoint.slide.macroenabled.12" :
		case "application/vnd.ms-powerpoint.slideshow.macroenabled.12" :
		case "application/vnd.ms-powerpoint.template.macroenabled.12" :
		case "application/vnd.openxmlformats-officedocument.presentationml.presentation" :
		case "application/vnd.openxmlformats-officedocument.presentationml.slide" :
		case "application/vnd.openxmlformats-officedocument.presentationml.slideshow" :
		case "application/vnd.openxmlformats-officedocument.presentationml.template" :
			$icon = "file-powerpoint-o";
			break;
		case "text/cache-manifest" :
		case "text/calendar" :
		case "text/css" :
		case "text/csv" :
		case "text/html" :
		case "text/x-php" :
			$icon = "file-code-o";
			break;
		case "application/mp21" :
		case "application/mp4" :
		case "application/ogg" :
			$icon = "file-audio";
			break;
		}

		if( null === $icon ) {
			if( preg_match( '/^text\//', $mime ) ) {
				$icon = "file-text-o";

			} elseif ( preg_match( '/^application\//', $mime ) ) {
				$icon = "file-code-o";

			} elseif ( preg_match( '/^audio\//', $mime ) ) {
				$icon = "file-audio-o";

			} elseif ( preg_match( '/^image\//', $mime ) ) {
				$icon = "file-image-o";

			} elseif ( preg_match( '/^video\//', $mime ) ) {
				$icon = "file-video-o";

			} else {
				$icon = "file-o";
			}
		}

		return $icon;
	}
}
