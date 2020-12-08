<?php

/**
 * Package installation functionality
 * @package Advertikon
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

/**
 * @property Advertikon $a
 * @property \DB $db
 * @property \Request $request
 * @property \Response $response
 * @property \Config $config
 * @property \Document $document
 * @property Load $load
 * @property \Language $language
 * @property \Session $session
 * @property \Url $url
 * @property \Cart\Customer $customer
 * @property \Cart\Cart $cart
 * @property \Cart\Currency $currency
 * @property \Log $log
 * @property \ModelExtensionExtension $model_extension_extension
 * @property \ModelExtensionModule $model_extension_module
 * @property \ModelSettingExtension $model_setting_extension
 * @property \ModelSettingModule $model_setting_module
 * @property \Cart\User $user
 */
trait Trait_Update {

	/**
	 * @throws Exception
	 */
	public function trait_update_init() {
		$this->a->add_listener( 'locale', [ 'action' => [ $this, 'populate_locale_update' ] ] );
	}

	/**
	 * Returns update button representation to run manual updating process
	 * @return string
	 * @throws Exception
	 */
	public function get_update_button() {
		$ret = '';

		if ( Advertikon::UPDATE_MANUAL === $this->update() ) {
			$ret = $this->a->r( array(
				'type'        => 'button',
				'icon'        => 'fa-database',
				'text_after'  => $this->a->__( 'Update DB data' ),
				'button_type' => 'primary',
				'custom_data' => "data-url=\"{$this->a->u( 'update_manual' )}\"",
				'id'          => 'update-button',
			) );
		}

		return $ret; 
	}

	/**
	 * Automatic update process
	 * @return int Update status
	 */
	public function update() {
		$this->a->console->profiler( 'update' );
		$this->a->debug( 'Checking for updates...' );
		$result = $this->updator( $this->get_updates(), $this );
		$this->a->console->profiler( 'update' );

		return $result;
	}

	/**
	 * Get list of updates
	 * @return array
	 */
	public function get_updates() {
		$updates = array();

		foreach( glob( $this->a->libray_dir . '/update/*' ) as $a ) {
			$updates[ basename( $a ) ] = $a;
		}

		if ( $updates ) {
			$this->a->debug( 'List of available updates', $updates );

		} else {
			$this->a->debug( 'No update is available' );
		}
		
		return $updates;
	}

	/**
	 * Manual update process
	 * @return void
	 */
	public function update_manual() {
		$this->a->log( 'Start manual update of DB data' );

		$ret = array();
		$status = $this->updator( $this->get_updates(), $this, true );

		if( Advertikon::UPDATE_SUCCESS === $status ) {
			$ret['success'] = $this->a->__( 'Data were successfully updated' );

		} elseif ( Advertikon::UPDATE_FAILED === $status ) {
			$ret['error'] = $this->a->__( 'Update failed' );
		}

		$this->response->setOutput( json_encode( $ret ) );
	}

	/**
	 * Rollback endpoint
	 * @return void
	 */
	public function rollback_db() {
		$ret = [];

		try {
			$new_version = $this->roll_back();
			$ret['success'] = $this->a->__( 'DB version has been rolled back' ) .
				( $new_version ? ' ' . $this->a->__( 'to version %s', $new_version ) : '' );

		} catch ( Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();

		} catch ( \Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $this->a->__( 'Failed to rollback db version' );
		}

		$this->response->setOutput( json_encode( $ret ) );
	}

	/**
	 * Rolls back DB one version down
	 * @return string New version
	 * @throws Exception
	 * @throws \Exception
	 */
	protected function roll_back() {
		$this->a->log( 'Start to rolling back the DB' );

		$version = $this->a->get_db_version();

		$this->a->log( sprintf( 'Current version is %s', $version ) );

		if ( !$version ) {
			throw new Exception( $this->a->__( 'There is no version to be rolled back' ) );
		}

		$file = $this->a->libray_dir . '/update/' . $version;

		if ( !is_file( $file ) ) {
			throw new Exception( $this->a->__( 'Update source is missing' ) );
		}

		$this->a->log( sprintf( 'Target file is %s', $file ) );

		require $file;
		$name = "update" . str_replace( '.', '_', $version );

		if ( !class_exists( $name ) ) {
			throw new Exception( $this->a->__( 'Update source is invalid' ) );
		}

		$this->a->log( sprintf( 'Target class is %s', $name ) );

		/** @var Update $update */
		$update = new $name( $this );

		if ( $update->rollback() ) {
			$this->a->log( 'Rollback succeeded' );

			$new_version = $this->a->get_db_version();

			$this->a->log( sprintf( 'New version is %s', $new_version ) );

			// Switch updator to manual mode
			Setting::set( 'update_version', $version, $this->a );

		} else {
			throw new \Exception( 'Failure' );
		}

		return $new_version;
	}

	/**
	 * Rolls back all updates
	 */
	public function roll_back_all() {
		try {
			while( $this->roll_back() ) {}
		} catch ( \Exception $e ) {}
	}

	/**
	 * Updates the extension
	 * @param array $updates List of available updates
	 * @param $controller
	 * @param bool $manual
	 * @return int
	 */
	public function updator( $updates, $controller, $manual = false ) {
		$current_version = $this->a->get_db_version();
		//$last_verson = null;

		$this->a->log( sprintf( 'Current DB version is: %s', $current_version ) );

		// Update has status in progress - last update failed
		if ( !$manual && ( $v = $this->a->config( 'update_version' ) ) ) {
			$this->a->debug( sprintf( 'Update %s failed, manual update is needed', $v ) );
			return Advertikon::UPDATE_MANUAL;
		}

		uksort( $updates, function( $a, $b ) { return version_compare( $a, $b ); } );

		foreach( $updates as $version => $file ) {
			if ( $this->a->can_update_db_version( $version ) ) {
				require $file;
				$name = "update" . str_replace( '.', '_', $version );

				// Mark that update is in process
				Setting::set( 'update_version', $version, $this->a );
				/** @var Update $update */
				$update = new $name( $controller );

				if ( false === $update->update() ) {
					$this->a->error( sprintf( 'Failed to apply update %s', $version ) );
					return Advertikon::UPDATE_FAILED;
				}
			}
		}

		// Mark the end of update
		Setting::set( 'update_version', '', $this->a );

		return Advertikon::UPDATE_SUCCESS;
	}

	public function _install() {
		if ( true !== ( $notification = $this->check_modifications() ) ) {
			echo $notification;
			$this->uninstall_from_system();
			$this->log->write( $this->a->code . ': Extension\'s modification is missing. Abort extension installation' );
			die;
		}

		$db_name = version_compare( VERSION, '3', '>=' ) ? DB_PREFIX . 'seo_url' : DB_PREFIX . 'url_alias';
		$q = $this->db->query( "SELECT * FROM $db_name WHERE `keyword` = 'adk_log'" );

		if ( $q && $q->num_rows < 1 ) {
			$route = $this->type . '/' . $this->code . '/log';

			$this->db->query( "INSERT INTO $db_name SET `keyword` = 'adk_log', `query` = '" . $route . "'" );
		}

		if ( trait_exists( '\Advertikon\Trait_Task', false ) ) {
			$this->install_task();
		}
	}

	/**
	 * @throws Exception
	 */
	public function _uninstall() {
		$db_name = version_compare( VERSION, '3', '>=' ) ? DB_PREFIX . 'seo_url' : DB_PREFIX . 'url_alias';

		// TODO: it will delete alias for the second installation as well
		$this->db->query( "DELETE FROM $db_name WHERE `keyword` = 'adk_log'" );

		$this->roll_back_all();
		$this->remove_db();
	}

	/**
	 * Remove extension tables from DB
	 * @return void
	 * @throws Exception
	 */
	public function remove_db() {
		foreach( $this->a->tables as $table ) {
			$result = $this->a->q( [
				'table' => $table,
				'query' => 'drop',
			] );

			if ( $result ) {
				$this->a->log( sprintf( "Table %s has been deleted", DB_PREFIX . $table ) );

			} else {
				$this->a->error( sprintf( "Failed to delete table %s", DB_PREFIX . $table ) );
			}
		}
	}

	/**
	 * Check if modifications were applied successfully and give some advises if not
	 * @return string|boolean True if everything is OK, error message otherwise
	 */
	public function check_modifications() {
		//global $adk_registry;

		$ret = true;

		// VQMOD is present in the system
		$vq = false;

		// VQMOD is enabled
		$vq_enabled = false;

		// VQMOD works in the system
		$vq_mod_added = false;

		// There is VQMOD modification pertains to the extension
		$vq_mod_works = false;

		// OCMOD modification pertains to the extension is present in DB
		$oc = false;

		// OCMOD modification is present in DB and is enabled
		$oc_mod_enabled = false;

		// OCMOD modification is applied
		$oc_mod_works = false;

		$this->a->console->profiler( 'modification' );

		$modifications_page = version_compare( VERSION, '3', '>=' ) ?
			$this->url->link( 'marketplace/modification', http_build_query( [ 'user_token' => $this->session->data['user_token'] ] ), 1 ) :
			$this->url->link( 'extension/modification', http_build_query( [ 'token' => $this->session->data['token'] ] ), 1 );

		$vq_xml_dir = dirname( DIR_SYSTEM ) . '/vqmod/xml';
		$vq_cache_dir = dirname( DIR_SYSTEM ) . '/vqmod/vqcache';
		$ocmod_file = $this->a->code . '.ocmod.xml';
		$vqmod_file = $this->a->code . '.vqmod.xml';
		$mod_name = $this->a->name ?: $this->a->code;
		$vq_modified_file_name = version_compare( VERSION, '3', '>=' ) ? '/*system_engine_router.php' : '/*system_engine_front.php';
		$oc_modifed_file_name = DIR_MODIFICATION . ( version_compare( VERSION, '3', '>=' ) ? 'system/engine/router.php' : 'system/engine/front.php' );

		if ( is_dir( $vq_xml_dir ) ) {
			$vq = true;
			$v = preg_match( '/VQMod::modCheck/', file_get_contents( DIR_APPLICATION . 'index.php' ) );

			if ( is_dir( $vq_cache_dir ) && glob( $vq_cache_dir . '/*' ) && $v ) {
				$vq_enabled = true;

				if ( is_file( $vq_xml_dir . '/' . $vqmod_file ) ) {
					$vq_mod_added = true;
				}

				$len = 0;
				$f = '';

				// Select the longest path - OCMOD + VQMOD
				foreach( glob( $vq_cache_dir . $vq_modified_file_name ) as $file ) {
					if ( strlen( $file ) > $len ) {
						$f = $file;
						$len = strlen( $file );
					}
				}

				if ( $f && preg_match( '/\$adk_registry = \$registry;/', file_get_contents( $f ) ) ) {
					$vq_mod_works = true;
				}
			}
		}

		$result = $this->db->query( "SELECT * FROM " . DB_PREFIX . "modification WHERE `code` = '" . $this->a->code . "'" );

		if ( $result->num_rows > 0 ) {
			$oc = true;

			if ( $result->row['status'] ) {
				$oc_mod_enabled = true;
			}
		}

		if ( file_exists( $oc_modifed_file_name ) && preg_match( '/\$adk_registry = \$registry;/', file_get_contents( $oc_modifed_file_name ) ) ) {
			$oc_mod_works = true;
		}

		$oc_system = is_file( DIR_SYSTEM . $ocmod_file );

		$div = $this->get_message_wrapper();

		// Take common folder for all the modules 
		$image = '<img src="' . substr( $this->a->image_url, 0, strrpos( rtrim( $this->a->image_url, '/' ), '/' ) )  .
			'/%s" style="width: 100%%; margin-top: 10px;">';

		try {
			if ( $oc_system && $oc_mod_enabled ) {
				$ret = sprintf(
					$div,
					sprintf( '%s: You have applied OCMOD modification twice: by placing file %s in folder /system and installing modification via Extension Installer. To avoid errors you need to remove file %s from /system folder',
						$mod_name,
						$ocmod_file,
						$ocmod_file
					)
				);

				throw new Exception( 'foo' );
			}

			if ( $vq_mod_works || $oc_mod_works ) {
				$ret = sprintf(
					$div,
					sprintf( '%s: for some reason modification was not applied automatically. In order to solve this issue send us support request to %s',
						$mod_name,
						$this->a->support_email
					)
				);

				throw new Exception( 'ok' );
			}

			if ( $oc && !$oc_mod_enabled ) {
				$ret = sprintf(
					$div,
					sprintf(
						'%s: In order, the module to work you need to enable its OCMOD modification from <a href="%s">Modifications page</a> %s',
						$mod_name,
						$modifications_page,
						sprintf( $image, 'enable_modification.png' )
					)
				);

				throw new Exception( 'foo' );
			}

			if ( $oc_system || ( $oc && $oc_mod_enabled ) ) {
				$ret = sprintf(
					$div,
					sprintf(
						'%s: In order, the module to work you need to refresh modifications from <a href="%s">Modifications page</a> %s',
						$mod_name,
						$modifications_page,
						sprintf( $image, 'refresh_modification.png' )
					)
				);

				throw new Exception( 'foo' );
			}

			if ( !$oc && !$vq ) {
				$ret = sprintf(
					$div,
					sprintf(
						'%s: for some reason modification was not applied automatically. Try to re-install the extension via the Extension Installer. Make sure you have no errors during the extension installation process. If you have problem installing the extension via Extension Installer you may upload contents of folder "upload" from the extension\'s package into the root folder of your store (if you renamed "admin" folder then you need to upload contents of package\'s upload\admin folder to that renamed folder) and put file %s from package root to /system folder of your store and refresh the modifications from Modification page. If during refreshing modifications you got an error then disable each modification one by one to see which one causes the error. If the above doesn\'t fit you - use VQMOD. Install VQMOD module and put file %s from package\'s root to /vqmod/xml folder of your store',
						$mod_name,
						$ocmod_file,
						$vqmod_file
					)
				);

				throw new Exception( 'foo' );
			}

			if ( $vq && !$vq_enabled ) {
				$ret = sprintf(
					$div,
					sprintf( '%s: In order, the module to work you need to enable VQMOD module. To do it refer this <a href="http://docs.opencart.com/administration/vqmod/" target="__blank">manual</a>',
						$mod_name
					)
				);

				throw new Exception( 'foo' );
			}

			if ( $vq_enabled && !$vq_mod_added ) {
				$ret = sprintf(
					$div,
					sprintf(
						'%s: In order, the module to work you need to put file %s from the root folder of extension\'s package file to \vqmod\xml folder of your store. For more details please refer this <a href="http://docs.opencart.com/administration/vqmod/" target="__blank">manual</a>',
						$mod_name,
						$vqmod_file
					)
				);

				throw new Exception( 'foo' );
			}

			$ret = sprintf(
				$div,
				sprintf( '%s: for some reason VQMOD modification was not applied automatically. In order to solve this issue send us support request to %s',
					$mod_name,
					$this->a->support_email
				)
			);

			throw new Exception( 'foo' );
			
		} catch ( Exception $e ) {
			if ( 'ok' === $e->getMessage() ) {
				$ret = true;
			}
		}

		$l = 32;

		if ( $this->a->is_console ) {
			$this->a->debug(
				"Modification check report:\n" .
				str_repeat( '-', $l + 7 ) . "\n" .
				sprintf( "%-{$l}s: %s\n", 'OCMODification is in DB', $oc ? 'true' : 'false' ) .
				sprintf( "%-{$l}s: %s\n", 'OCMODification is enabled', $oc_mod_enabled ? 'true' : 'false' ) .
				sprintf( "%-{$l}s: %s\n", 'OCMODification works', $oc_mod_works ? 'true' : 'false' ) .
				sprintf( "%-{$l}s: %s\n", 'OCMOD file is in the /system dir', $oc_system ? 'true' : 'false' ) .
				sprintf( "%-{$l}s: %s\n", 'VQMOD is present', $vq ? 'true' : 'false' ) .
				sprintf( "%-{$l}s: %s\n", 'VQMOD is enabled', $vq_enabled ? 'true' : 'false' ) .
				sprintf( "%-{$l}s: %s\n", 'VQMODification is added', $vq_mod_added ? 'true' : 'false' ) .
				sprintf( "%-{$l}s: %s\n", 'VQMOD works', $vq_mod_works ? 'true' : 'false' ) .
				str_repeat( '-', $l + 7 ) . "\n \n"
			);
		}

		$this->a->console->profiler( 'modification' );

		return $ret;
	}
	
	/**
	 * Un-installs an extension from OpenCart
	 */
	private function uninstall_from_system() {
		$type = strstr( $this->a->type, '/' );
		
		if ( $type !== false ) {
			$type = substr( $type, 1 );
			
		} else {
			$type = $this->a->type;
		}
		
		if ( version_compare( VERSION, '2.3', '<' ) ) {
			$this->load->model( 'extension/extension' );
			$this->model_extension_extension->uninstall( $type, $this->a->code );
			
			if ( 'module' === $type ) {
				$this->load->model( 'extension/module' );
				$this->model_extension_module->deleteModulesByCode( $this->a->code );
			}

		} else {
			$this->load->model( 'setting/extension' );
			$this->model_setting_extension->uninstall( $type, $this->a->code );
			
			if ( 'module' === $type ) {
				$this->load->model( 'setting/module' );
				$this->model_setting_module->deleteModulesByCode( $this->a->code );
			}
		}
	}
	
	/**
	 * Returns notification wrapper in a form of sprintf string
	 * @return string
	 */
	protected function get_message_wrapper() {
		return '<div style="font-size: 13px; color: #9c1414;">%s</div>';
	}
	
	protected function check_db_consistency() {
		$this->a->console->profiler( 'db consistency' );
		$tables = $this->db->query( "SHOW tables" );
		$ok = true;
		
		if ( !$tables || !$tables->rows ) {
			$this->a->error( 'Failed to check DB consistency due to error' );
			$this->a->console->profiler( 'db consistency' );
			
			return true;
		}

		foreach( $this->a->tables as $table ) {
			foreach( $tables->rows as $oc_table ) {
				if ( is_array( $oc_table ) ) {
					$oc_table = reset( $oc_table );
				}

				if ( $oc_table === DB_PREFIX . $table ) {
					continue 2;
				}
			}
			
			$ok = false;
			break;
		}
		
		$this->a->console->profiler( 'db consistency' );
		
		if ( !$ok ) {
			return sprintf(
				$this->get_message_wrapper(),
				sprintf(
					'%s: Some database tables are missing. In order to fix '
					. 'this issue you need to uninstall/install the extension '
					. 'at the Extensions page',
					$this->a->name ?: $this->a->code
				)
			);
		}
		
		return $ok;
	}
	
	public function set_setting() {
		$name = $this->a->post( 'name' );
		$value = $this->a->post( 'value' );
		$ret = [];
		
		try {
			if ( !$this->a->has_permissions( Advertikon::PERMISSION_MODIFY ) ) {
				throw new Exception( 'You have no sufficient permissions' );
			}
			
			if ( !$name ) {
				throw new Exception( 'Name is missing' );
			}
			
			if ( is_null( $value ) ) {
				throw new Exception( 'Value is missing' );
			}
			
			$response = Setting::set( $name, $value, $this->a );
			
			if ( !$response ) {
				throw new Exception( 'Failed to save configuration' );
			}
			
			$ret['success'] = $this->a->__( 'Configuration have been saved' );
			
		} catch ( \Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();
		}
		
		$this->response->setOutput( json_encode( $ret ) );
	}

	/**
	 * @param $locale
	 * @throws Exception
	 */
	public function populate_locale_update(&$locale ) {
		$locale = array_merge( [
			'settingUrl' => $this->a->u( 'set_setting' ),
		], $locale );
	}
}
