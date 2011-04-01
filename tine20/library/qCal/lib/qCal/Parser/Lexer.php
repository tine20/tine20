<?php
/**
 * qCal_Parser_Lexer
 * This class provides the basic structure of a lexer, which converts iCalendar
 * data, in whatever format it is in (iCalendar, xCalendar, hCalendar, etc.)
 * into a list of tokens, which are then fed to the parser, which converts them
 * into qCal_Component, qCal_Property, etc. values.
 * 
 * @package qCal
 * @subpackage qCal_Parser
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 */ 
abstract class qCal_Parser_Lexer {

	/**
	 * @var string input text
	 */
	protected $content;
	/**
	 * Constructor
	 * @param string containing the text to be tokenized
	 */
	public function __construct($content) {
	
		$this->content = $content;
	
	}
	/**
	 * Tokenize content into tokens that can be used to build iCalendar objects
	 */
	abstract public function tokenize();

}