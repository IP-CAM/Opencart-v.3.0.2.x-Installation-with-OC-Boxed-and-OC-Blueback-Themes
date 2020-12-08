<?php
namespace Advertikon;

class Twig {
	protected $twig;
	protected $data = array();
	protected $a = null;
	
	public function __construct( $a ) {
		if ( !class_exists( 'Twig_Autoloader', false ) ) {
			if ( is_file( DIR_SYSTEM . 'library/template/Twig/Autoloader.php' ) ) {
				new \Template\Twig( null ); // Dreamvention fix

			} else {
				include_once( __DIR__ . '/Twig/Autoloader.php' );
				\Twig_Autoloader::register();
			}
		}

		$this->a = $a;
		
		// specify where to look for templates
		$loader = new \Twig_Loader_Filesystem( DIR_TEMPLATE );

		if ( defined( 'DIR_DEFAULT_TEMPLATE' ) ) { // Omtext fix
			$loader->addPath( DIR_DEFAULT_TEMPLATE );
		}

		// initialize Twig environment
		$config = [
			'autoescape'  => false,
			'cache'       => DIR_CACHE,
			'auto_reload' => true,
			'debug'       => true,
		];

		$this->twig = new \Twig_Environment( $loader, $config );
		$this->twig->addExtension( new \Twig_Extension_Debug() );
		$this->add( $a );
	}

	protected function add( $a ) {
		$translate_func = new \Twig_SimpleFunction( '__', function ( $str ) use ( $a ) {
		    return call_user_func_array( [ $a, '__' ], func_get_args() );
		} );

		$this->twig->addFunction( $translate_func );

		$config_func = new \Twig_SimpleFunction( 'c', function ( $str, $def = '' ) use( $a ) {
		    return $a->config( $str, $def );
		} );

		$this->twig->addFunction( $config_func );

		$url_func = new \Twig_SimpleFunction( 'u', function ( $route, $query = [] ) use( $a ) {
		    return $a->u( $route, $query );
		} );

		$this->twig->addFunction( $url_func );

		$url_catalog = new \Twig_SimpleFunction( 'catalog_url', function () use( $a ) {
		    return $a->u()->catalog_url();
		} );

		$this->twig->addFunction( $url_catalog );

		$script_catalog = new \Twig_SimpleFunction( 'catalog_script', function ( $js ) use( $a ) {
		    return $a->u()->catalog_script( $js );
		} );

		$this->twig->addFunction( $script_catalog );

		$css_catalog = new \Twig_SimpleFunction( 'catalog_css', function ( $css ) use( $a ) {
		    return $a->u()->catalog_css( $css );
		} );

		$this->twig->addFunction( $css_catalog );

		$configuration_func = new \Twig_SimpleFunction( 'config', function ( $str ) use ( $a ) {
		    return $a->config->get( $str );
		} );

		$this->twig->addFunction( $configuration_func );

		$image_func = new \Twig_SimpleFunction( 'image', function ( $str ) use ( $a ) {
			if ( !$str ) return '';
	
			// Joomla fix
		   return ( defined( 'HTTP_IMAGE' ) ? HTTP_IMAGE : ( $a->u()->catalog_url() . 'image/' ) ) . $str;
		} );

		$this->twig->addFunction( $image_func );

		$caption_func = new \Twig_SimpleFunction( 'caption', function ( $str ) use ( $a ) {
		    return $a->caption( $str );
		} );

		$this->twig->addFunction( $caption_func );

		$printf_func = new \Twig_SimpleFunction( 'printf', function ( $str ) {
		    return call_user_func_array( 'sprintf', func_get_args() );
		} );

		$this->twig->addFunction( $printf_func );

		$date_func = new \Twig_SimpleFunction( 'date', function ( $str ) {
		    return call_user_func_array( 'date', func_get_args() );
		} );

		$this->twig->addFunction( $date_func );

		$convert_func = new \Twig_SimpleFunction( 'currency_convert', function ( $value, $from, $to ) use ( $a ) {
		    return $a->currency->convert( $value, $from, $to );
		} );

		$this->twig->addFunction( $convert_func );

		$format_func = new \Twig_SimpleFunction( 'currency_format', function ( $number, $currency, $value = '', $format = true ) use ( $a ) {
		    return $a->currency->format( $number, $currency, $value, $format );
		} );

		$this->twig->addFunction( $format_func );

		$filter_html_decode = new \Twig_SimpleFilter( 'html_decode', function ( $string ) {
			return htmlspecialchars_decode( $string );
		} );

		$this->twig->addFilter( $filter_html_decode );

		$filter_wrap_p = new \Twig_SimpleFilter( 'wrap_p', function ( $string ) {
			$parts = preg_split( '/\r?\n/', $string );
			return '<p>' . implode( '</p><p>', $parts ) . '</p>';
		} );

		$this->twig->addFilter( $filter_wrap_p );

		$this->twig->addTokenParser( new Parser\Func() );
	}
	
	public function render( $template, $data ) {
		try {
			if ( $pos = strrpos( $template, '.twig' ) ||  $pos = strrpos( $template, '.tpl' )  ) {
				$template = substr( $template, 0 , $pos );
			}

			// Absolute path
			if ( $template[ 0 ] === '/' ) {
				$base = $this->get_base_dir( $template );

				$this->twig->getLoader()->addPath( $base );
				$template = substr( $template, strlen( $base ) );

			} elseif ( !$this->a->is_admin() && strpos( $template, 'default/template/' ) !== 0 ) {
				$template = 'default/template/' . $template;
			}

			// load template
			$template = $this->twig->loadTemplate( $template . '.twig' );
			
			return $template->render( $data );
		} catch (Exception $e) {
			throw new \Exception('Error: Could not load template ' . $template . '!');
		}	
	}

	protected function get_base_dir( $template ) {
		$base = '';

		if ( ( $len = $this->base_part( $template, DIR_SYSTEM ) ) > 0 ) {
			$base = substr( $template, 0, $len );
		}

		if ( !$base || !is_readable( $base ) ) {
			if ( ( $len = $this->base_part( $template, DIR_STORAGE ) ) > 0 ) {
				$base = substr( $template, 0, $len );
			}
		}

		return $base && is_readable( $base ) ? $base : '/';
	}

	protected function base_part( $a, $b ) {
		for( $i = 0; $i < strlen( $a ); $i++ ) {
			if ( !isset( $b[ $i ] ) || $a[ $i ] !== $b[ $i ] ) {
				break;
			}
		}

		return $i;
	}
}
