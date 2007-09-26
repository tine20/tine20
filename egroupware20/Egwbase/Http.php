<?php
/**
 * HTTP interface to Egwbase
 *
 * @author Lars Kneschke <l.kneschke@metaways.de>
 * @package Egwbase
 *
 */
class Egwbase_Http
{
	/**
	 * displays the login dialog
	 *
	 */
	public function login()
	{
		$view = new Zend_View();

		$view->setScriptPath('Egwbase/views');

		header('Content-Type: text/html; charset=utf-8');
		echo $view->render('login.php');
	}

	public function mainScreen()
	{
		$userApplications = array('Addressbook');
		$view = new Zend_View();

		$view->setScriptPath('Egwbase/views');

		//foreach(array('Felamimail', 'Addressbook', 'Asterisk') as $applicationName) {
		foreach($userApplications as $applicationName) {
			$className = "{$applicationName}_Json";
				
			$application = new $className;
				
			$applications[] = $application->getMainTree();
				
			$jsIncludeFiles[] = $applicationName . '/Js/' . $applicationName . '.js';
		}

		$view->jsIncludeFiles = $jsIncludeFiles;
		$view->applications = $applications;
		$view->title="eGroupWare 2.0";

		header('Content-Type: text/html; charset=utf-8');
		echo $view->render('mainscreen.php');
	}
}