<?php
/**
 * Advertikon Twig Tag parser class
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
 */

namespace Advertikon\Parser;

use Twig_Node_Expression;
use Twig_Compiler;
use Twig_Node;

class Func_Node extends Twig_Node {

    public function __construct( $name, $arguments, $line, $tag = null ) {
        parent::__construct( array( 'arguments' => $arguments ), array( 'name' => $name ), $line, $tag );
    }

    public function compile( Twig_Compiler $compiler ) {
		$compiler
			->addDebugInfo( $this )
			->write( sprintf( 'echo call_user_func( "%s",', $this->getAttribute( 'name' ) ) );


		$count = count($this->getNode('arguments'));
		$pos = 0;

		foreach ( $this->getNode( 'arguments' ) as $default ) {
			$compiler->subcompile($default);

			if (++$pos < $count) {
				$compiler->raw( ', ' );
			}
		}

		$compiler->raw( ");\n" );
    }
}
