<?php
/**
 * qCal_Parser_Lexer_iCalendar
 * The lexer for iCalendar RFC 2445 format. Other formats will need their
 * own lexer. The lexer converts text to an array of "tokens", which, at least
 * for now, are just arrays.
 * 
 * @package qCal
 * @subpackage qCal_Parser
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 * @todo Make sure that multi-value properties are taken care of properly
 */
class qCal_Parser_Lexer_iCalendar extends qCal_Parser_Lexer {
	
	/**
	 * @var string character(s) used to terminate lines
	 */
	protected $line_terminator;
	
	/**
	 * Constructor 
	 * @param string The iCalendar data to be parsed
	 * @access public
	 */
	public function __construct($content) {
	
		parent::__construct($content);
		$this->line_terminator = chr(13) . chr(10);
	
	}
	/**
	 * Return a list of tokens (to be fed to the parser)
	 * @return array tokens
	 * @access public
	 */
	public function tokenize() {
	
		// iCalendar data is "folded", which means that long lines are broken
		// into smaller lines and proceded by a space. The lines are "unfolded"
		// before being parsed.
		$lines = $this->unfold($this->content);
		// loop through chunks of input text by separating properties and components
		// and create tokens for each one, creating a multi-dimensional array of tokens to return
		// this "stack" array is used to keep track of the "current" component
		$stack = array();
		foreach ($lines as $line) {
			// each component starts with the string "BEGIN:" and doesn't end until the "END:" line
			if (preg_match('#^BEGIN:([a-z]+)$#i', $line, $matches)) {
				// create new array representing the new component
				$array = array(
					'component' => $matches[1],
					'properties' => array(),
					'children' => array(),
				);
				// add the component to the stack
				$stack[] = $array;
			} elseif (strpos($line, "END:") === 0) {
				// end component, pop the stack
				$child = array_pop($stack);
				if (empty($stack)) {
					// if the stack is empty, assign the $child variable to the $tokens variable
					$tokens = $child;
				} else {
					// if the stack is not empty, create a reference of the last item in the stack
					$parent =& $stack[count($stack)-1];
					// add the child to the parent
					array_push($parent['children'], $child);
				}
			} else {
				// continue component
				if (preg_match('#^([^:]+):"?([^\n]+)?"?$#i', $line, $matches)) {
					// @todo What do I do with empty values?
					$value = isset($matches[2]) ? $matches[2] : "";
					// set the $component variable to a reference of the last item in the stack
					$component =& $stack[count($stack)-1];
					// if line is a property line, start a new property, but first determine if there are any params
					$property = $matches[1];
					$params = array();
					$propparts = explode(";", $matches[1]);
					if (count($propparts) > 1) {
						foreach ($propparts as $key => $part) {
							// the first one is the property name
							if ($key == 0) {
								$property = $part;
							} else {
								// the rest are params
								// @todo Quoted param values need to be taken care of...
								list($paramname, $paramvalue) = explode("=", $part, 2);
								$params[] = array(
									'param' => $paramname,
									'value' => $paramvalue,
								);
							}
						}
					}
					// set up the property array
					$proparray = array(
						'property' => $property,
						'value' => $value,
						'params' => $params,
					);
					// assign the property array to the current component
					$component['properties'][] = $proparray;
				}
			}
		}
		// we should now have an associative, multi-dimensional array of tokens to return
		return $tokens;
	
	}
	/**
	 * Long lines inside of an iCalendar file are "folded". This means that
	 * they are broken into smaller lines and prepended with a space
	 * character. This method "unfolds" these lines into one long line again.
	 * @param string $content The iCalendar data to be unfolded
	 * @return string The iCalendar data, unfolded
	 * @access protected
	 */
	protected function unfold($content) {
	
		$return = array();
		$lines = explode($this->line_terminator, $content);
		foreach ($lines as $line) {
			$checkempty = trim($line);
			if (empty($checkempty)) continue;
			$chr1 = substr($line, 0, 1);
			$therest = substr($line, 1);
			// if character 1 is a whitespace character... (tab or space)
			if ($chr1 == chr(9) || $chr1 == chr(32)) {
				$return[count($return)-1] .= $therest;
			} else {
				$return[] = $line;
			}
		}
		return $return;
	
	}

}