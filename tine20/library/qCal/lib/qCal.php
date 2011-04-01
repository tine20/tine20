<?php
/**
 * qCal Object
 * 
 * @package qCal
 * @copyright Luke Visinoni (luke.visinoni@gmail.com)
 * @author Luke Visinoni (luke.visinoni@gmail.com)
 * @license GNU Lesser General Public License
 * @todo I am considering kicking qCal_Component_Vcalendar to the curb and just using this... I don't know though.
 */
class qCal extends qCal_Component_Vcalendar {

	// this is simply a facade to qCal_Component_Calendar, a shortcut
	
	/**
	 * I don't actually need any of these, but I felt like putting them here
	 * so that they are available if and when I need them. I am considering
	 * using __sleep() and __wakeup(). I'm also trying to think of good uses for
	 * __destruct() and __invoke(). Not just for this class, but all of them.
	public function __toString() {
		
	}
	public function __isset($key) {
		
	}
	public function __unset($key) {
		
	}
	public function __get($key) {
		
	}
	public function __set($key, $value) {
		
	} 
	public static function __set_state(Array $properties) {
		
	}
	public function __sleep() {
		
	}
	public function __wakeup() {
		
	}
	public function __invoke() {
		
	}
	public function __clone() {
		
	}
	public function __destruct() {
		
	}
	 */


}