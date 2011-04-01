<?php
/**
 * Default icalendar renderer. Pass a component to the renderer, and it will render it in accordance with rfc 2445
 * @package qCal
 * @subpackage qCal_Renderer
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 */ 
class qCal_Renderer_iCalendar extends qCal_Renderer {

	/**
	 * iCalendar uses Windows line endings
	 */
	const LINE_ENDING = "\r\n";
	/**
	 * Fold lines at 75 octets
	 */
	const FOLD_LENGTH = 75;
	/**
	 * Render any qCal_Component object in accordance with RFC 2445
	 * @param qCal_Component The component that is to be rendered
	 * @return string The component (and all sub-components and properties)
	 * @access public
	 * rendered into iCalendar format.
	 */
	public function render(qCal_Component $component) {
	
		$return = "BEGIN:" . $component->getName() . self::LINE_ENDING;
		foreach ($component->getProperties() as $property) {
			if (is_array($property)) {
				foreach ($property as $prop) {
					$return .= $this->renderProperty($prop);
				}
			} else {
				$return .= $this->renderProperty($property);
			}
		}
		foreach ($component->getChildren() as $children) {
			if (is_array($children)) {
				foreach ($children as $child) {
					$return .= $this->render($child);
				}
			} else {
				$return .= $this->render($children);
			}
		}
		return $return . "END:" . $component->getName() . self::LINE_ENDING;
	
	}
	/**
	 * Renders a property in accordance with rfc 2445
	 * @param qCal_Property The property that is to be rendered
	 * @return string The property rendered into iCalendar format
	 * @access protected
	 */
	protected function renderProperty(qCal_Property $property) {
	
		$propval = $property->getValue();
		$params = $property->getParams();
		$paramreturn = "";
		foreach ($params as $paramname => $paramval) {
			$paramreturn .= $this->renderParam($paramname, $paramval);
		}
		// if property has a "value" param, then use it as the type instead
		$proptype = isset($params['VALUE']) ? $params['VALUE'] : $property->getType();
		if ($property instanceof qCal_Property_MultiValue) {
			$values = array();
			foreach ((array)$property->getValue() as $value) {
				$values[] = $this->renderValue($property->getValue(), $proptype);
			}
			$value = implode(chr(44), $values);
		} else {
			$value = $this->renderValue($property->getValue(), $proptype);
		}
		$content = $property->getName() . $paramreturn . ":" . $value . self::LINE_ENDING;
		return $this->fold($content);
	
	}
	/**
	 * Renders a value 
	 * @param string $value The value that is to be rendered
	 * @param string $type The type that it is to be rendered as
	 * @return string The rendered value
	 * @access protected
	 * @todo Make sure that there aren't any other types that need to be taken care of here
	 */
	protected function renderValue($value, $type) {
	
		switch(strtoupper($type)) {
			case "TEXT":
				$value = str_replace(",", "\,", $value);
				break;
		}
		return $value;
	
	}
	/**
	 * Renders a parameter
	 * RFC 2445 says if paramval contains COLON (US-ASCII decimal
	 * 58), SEMICOLON (US-ASCII decimal 59) or COMMA (US-ASCII decimal 44)
	 * character separators MUST be specified as quoted-string text values
	 * @param string $name The name of the param to be rendered
	 * @param string $value The parameter value
	 * @return The rendered parameter
	 * @access protected
	 */
	protected function renderParam($name, $value) {
	
		$invchars = array(chr(58),chr(59),chr(44));
		$quote = false;
		foreach ($invchars as $char) {
			if (strstr($value, $char)) {
				$quote = true;
				break;
			}
		}
		if ($quote) $value = '"' . $value . '"';
		return ";" . $name . "=" . $value;
	
	}
	
	/**
	 * Text cannot exceed 75 octets. This method will "fold" long lines in accordance with RFC 2445
	 * @param string $data The data to be unfolded
	 * @return The data "unfolded"
	 * @access protected
	 * @todo Make sure this is multi-byte safe
	 * @todo The file I downloaded from google used this same folding method (long lines went to 76)
	 * so until I see any different, I'm going to keep it at 76.
	 */
	protected function fold($data) {
	
		if (strlen($data) == (self::FOLD_LENGTH + strlen(self::LINE_ENDING))) return $data;
		$apart = str_split($data, self::FOLD_LENGTH);
		return implode(self::LINE_ENDING . " ", $apart);
	
	}

}