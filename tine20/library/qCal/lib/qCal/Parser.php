<?php
/**
 * qCal_Parser
 * The parser uses a "lexer" class to convert data from any of several
 * iCalendar file formats (iCalendar, xCalendar, hCalendar, etc.) to native
 * qCal components so that they may be worked with using objects.
 * 
 * @package qCal
 * @subpackage qCal_Parser
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 */ 
class qCal_Parser {

	/**
	 * @var array containing any options the particular parser accepts
	 */
	protected $options;
	/**
	 * Constructor
	 * Pass in an array of options
	 * @param array parser options
	 * @access public
	 * @todo Come up with more options
	 */
	public function __construct($options = array()) {
	
		// set defaults...
		$this->options = array(
			'searchpath' => get_include_path(),
		);
		$this->options = array_merge($this->options, $options);
	
	}
	/**
	 * Parse iCalendar data, in whatever format it might be in
	 * @param string $content The raw iCalendar data to parse
	 * @param qCal_Parser_Lexer 
	 * @return qCal_Component_Vcalendar
	 * @access public
	 */
	public function parse($content, qCal_Parser_Lexer $lexer = null) {
	
		if (is_null($lexer)) {
			$lexer = new qCal_Parser_Lexer_iCalendar($content);
		}
		$this->lexer = $lexer;
		return $this->doParse($this->lexer->tokenize());
	
	}
	/**
	 * Parse a file. The searchpath defaults to the include path. Also, if the filename
	 * provided is an absolute path, the searchpath is not used. This is determined by 
	 * either the file starting with a forward slash, or a drive letter (for Windows)
	 * @param string $filename The name of the file to parse
	 * @return qCal_Component_Vcalendar
	 * @throws qCal_Exception_FileNotFound If $filename cannot be found
	 * @access public
	 * @todo I'm not really sure that it should default to the include path. That's not really what the include path is for, is it?
	 * @todo Test for path starting with a drive letter for windows (or find a better way to detect that)
	 */
	public function parseFile($filename) {
	
		// @todo This is hacky... but it works
		if (substr($filename, 0, 1) == '/' || substr($filename, 0, 3) == 'C:\\') {
			if (file_exists($filename)) {
				$content = file_get_contents($filename);
				return $this->parse($content);
			}
		} else {
			$paths = explode(PATH_SEPARATOR, $this->options['searchpath']);
			foreach ($paths as $path) {
				$fname = $path . DIRECTORY_SEPARATOR . $filename;
				if (file_exists($fname)) {
					$content = file_get_contents($fname);
					return $this->parse($content);
				}
			}
		}
		throw new qCal_Exception_FileNotFound('File cannot be found: "' . $filename . '"');
	
	}
	/**
	 * Parses any of several iCalendar formats (iCalendar, xCalendar, hCalendar) into qCal's native qCal components
	 * Override doParse in a child class if necessary
	 * @param array $tokens An array of arrays containing components, properties and parameters
	 * @return qCal_Component_Vcalendar
	 * @access protected
	 */
	protected function doParse($tokens) {
	
		$properties = array();
		foreach ($tokens['properties'] as $propertytoken) {
			$params = array();
			foreach ($propertytoken['params'] as $paramtoken) {
				$params[$paramtoken['param']] = $paramtoken['value'];
			}
			try {
				$properties[] = qCal_Property::factory($propertytoken['property'], $propertytoken['value'], $params);
			} catch (qCal_Exception $e) {
				// @todo There should be a better way of determining what went wrong during parsing/lexing than this
				// do nothing...
				// pr($e);
			}
		}
		$component = qCal_Component::factory($tokens['component'], $properties);
		foreach ($tokens['children'] as $child) {
			$childcmpnt = $this->doParse($child);
			$component->attach($childcmpnt);
		}
		return $component;
	
	}

}