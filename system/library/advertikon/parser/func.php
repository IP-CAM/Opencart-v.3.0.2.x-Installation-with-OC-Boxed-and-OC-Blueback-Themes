<?php
/**
 * Advertikon Twig Tag parser class
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
 */

namespace Advertikon\Parser;

use Twig_TokenParser;
use Twig_Token;

class Func extends Twig_TokenParser {
    public function parse( Twig_Token $token ) {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(Twig_Token::NAME_TYPE)->getValue();
        $arguments = $this->parser->getExpressionParser()->parseArguments();
        $stream->expect(Twig_Token::BLOCK_END_TYPE);

        return new Func_Node( $name, $arguments, $token->getLine(), $this->getTag() );
    }

    public function getTag() {
        return '_';
    }
}
