<?php

namespace Advertikon;

/**
 * Class Load
 * @package Advertikon
 * @method  \Model model( $name )
 * @method \Language language( $name )
 * @method Twig view( $template_route, array $data )
 */
class Load {

    /** @var \Loader  */
	private $parent = null;
	private $a = null;

	/** @var Twig  */
	private $twig = null;
	private $models = [];

	public function __construct( $parent, $a ) {
		$this->parent = $parent;

		/** @var Advertikon $a */
		$this->a = $a;
	}

    /**
     * @param $name
     * @param $args
     * @return Placeholder|Tax|mixed|string
     * @throws \Exception
     */
    public function __call($name, $args ) {
		if ( 'view' === $name ) { 
			if ( !$this->twig ) {
				$classname = preg_replace( '/Advertikon$/', 'Twig', get_class( $this->a ) );
				$this->twig = new $classname( $this->a );
			}

			return $this->twig->render( $this->check_mod( $args[ 0 ], 'view' ), $args[ 1 ] );

		} elseif ( 'model' === $name ) {
			$name = (string)$args[ 0 ];

			if ( version_compare( VERSION, '3', '>=' ) ) {
				if ( 'extension/extension' === $name ) {
					$name = 'setting/extension';
				}

			} else if ( version_compare( VERSION, '2.1.0.1', '<' ) ) {
				if ( 'customer/customer_group' === $name && $this->a->is_admin() ) {
					$name = 'sale/customer_group';
				}
			}

			$this->parent->model( $name );
			$model_name = "model_" . str_replace(array('/', '-', '.'), array('_', '', ''), $name );

			return $this->a->$model_name;
		
		} elseif ( method_exists( $this->parent, $name ) ) {
			return call_user_func_array( [ $this->parent, $name ], $args );

		} else {
			$this->a->error( new Exception( sprintf( 'Loader doesn\'t have method "%s"', $name ) ) );

			return new Placeholder;
		}
	}

	protected function check_mod( $file, $type = 'controller' ) {
		return $this->check_ocmod( $file, $type );
	}

	protected function check_ocmod( $file, $type ) {
		$mod = null;
		$fs = new Fs();

		switch ( strtolower( $type ) ) {
			case 'view':
				if ( $this->a->is_admin() ) {
					if ( '/' !== $file[ 0 ] ) {
						$full_file = 'admin/view/template/' . $file;

					} else {
						$full_file = substr( $file, strlen( dirname( DIR_SYSTEM ) ) + 1 ); 
					}

					$mod = DIR_MODIFICATION . $full_file;

				} else {
					//TODO
				}

				return $mod && file_exists( $mod . '.twig' ) ? $mod : $file;
			break;
			case 'controller':
				if ( $this->a->is_admin() ) {
					$full_file = 'admin/controller/' . $file;

				} else {
					$full_file = 'catalog/controller/' . $file;
				}

				$mod = DIR_MODIFICATION . $full_file;
			break;
		}

		return $mod && file_exists( $mod ) ? $mod : $file;
	}

	public function get_model( $cache_name ) {
		if ( isset( $this->models[ $cache_name ] ) ) {
			return $this->models[ $cache_name ];
		}
	}

	/**
	 * Sort of compatibility fix
	 * @param string $route 
	 * @return string
	 */
	protected function get_model_route( $route ) {
		if( version_compare( VERSION, '3', '>=' ) ) {
			switch ( $route ) {
				case 'extension/extension':
					$route = 'setting/extension';
					break;
				default:
			}
		}

		return $route;
	}
}
