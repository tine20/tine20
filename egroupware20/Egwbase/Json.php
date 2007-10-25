<?php
/**
 * Json interface to Egwbase
 *
 * @author Lars Kneschke <l.kneschke@metaways.de>
 * @package Egwbase
 *
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
        $egwBaseNamespace = new Zend_Session_Namespace('egwbase');

        $auth = Zend_Auth::getInstance();

        $authAdapter = Egwbase_Auth::factory(Egwbase_Auth::SQL);

        $authAdapter->setIdentity($username)
        ->setCredential($password);
        	
        $result = $auth->authenticate($authAdapter);

        if ($result->isValid()) {
            $egwBaseNamespace->isAutenticated = TRUE;
            $egwBaseNamespace->currentAccount = $authAdapter->getResultRowObject(NULL, array('account_pwd'));

            $response = array(
				'success'        => TRUE,
                'welcomeMessage' => "Some welcome message!"
			);
					
			$accesslog = new Egwbase_AccessLog();
            
			$accesslog->addLoginEntry(
    			session_id(),
    			$egwBaseNamespace->currentAccount->account_lid,
    			$_SERVER['REMOTE_ADDR'],
    			$egwBaseNamespace->currentAccount->account_id,
    			$result->getCode()
			);

        } else {
            $egwBaseNamespace->isAutenticated = FALSE;

            $response = array(
				'success'      => FALSE,
				'errorMessage' => "Wrong username or passord!"
			);

			$accesslog = new Egwbase_AccessLog();
			$accesslog->addLoginEntry(
    			Egwbase_AccessLog::LOGIN,
    			session_id(),
    			$egwBaseNamespace->currentAccount->account_lid,
    			$_SERVER['REMOTE_ADDR'],
    			$egwBaseNamespace->currentAccount->account_id,
    			$result->getCode()
			);
			sleep(2);
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
        $currentAccount = Zend_Registry::get('currentAccount');

        $accesslog = new Egwbase_AccessLog();
        $accesslog->addLogoutEntry(
            session_id(),
            $_SERVER['REMOTE_ADDR'],
            $currentAccount->account_id
        );
        
        Zend_Session::destroy();

        $result = array(
			'success'=> true,
        );

        return $result;
    }
}
?>