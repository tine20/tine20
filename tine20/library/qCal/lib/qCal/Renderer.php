<?php
/**
 * The basic structure of a renderer. All of the functionality is delegated to child classes.
 * @package qCal
 * @subpackage qCal_Renderer
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 */
abstract class qCal_Renderer {

	abstract public function render(qCal_Component $component);
	abstract protected function renderProperty(qCal_Property $property);
	abstract protected function renderValue($value, $type);
	abstract protected function renderParam($name, $value);

}