<?php
/**
 * eGroupWare 2.0
 * 
 * @package     Egwbase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Json interface to Egwbase
 */
class Egwbase_Json
{
    /**
     * get list of translated country names
     *
     * @return array list of countrys
     */
    function getCountryList()
    {
        $locale = Zend_Registry::get('locale');

        $countries = $locale->getCountryTranslationList();
        asort($countries);
        foreach($countries as $shortName => $translatedName) {
            $results[] = array(
				'shortName'         => $shortName, 
				'translatedName'    => $translatedName
            );
        }

        $result = array(
			'results'	=> $results
        );

        return $result;
    }

    /**
     * authenticate user by username and password
     *
     * @param string $username the username
     * @param string $password the password
     * @return array
     */
    function login($username, $password)
    {
        $result = Egwbase_Controller::getInstance()->login($username, $password, $_SERVER['REMOTE_ADDR']);
        
        $egwBaseNamespace = new Zend_Session_Namespace('egwbase');

        if ($result->isValid()) {
            $egwBaseNamespace->isAutenticated = TRUE;

            $response = array(
				'success'        => TRUE,
                'welcomeMessage' => "Some welcome message!"
			);
        } else {
            $egwBaseNamespace->isAutenticated = FALSE;

            $response = array(
				'success'      => FALSE,
				'errorMessage' => "Wrong username or passord!"
			);
        }


        return $response;
    }

    /**
     * destroy session
     *
     * @return array
     */
    function logout()
    {
        Egwbase_Controller::getInstance()->logout($_SERVER['REMOTE_ADDR']);
        
        $result = array(
			'success'=> true,
        );

        return $result;
    }
}
?>