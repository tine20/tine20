<?php
class Egwbase_Json
{
	function getCountryList()
	{
		$locale = Zend_Registry::get('locale');
		
                $countries = $locale->getCountryTranslationList();
                asort($countries);
		foreach($countries as $shortName => $translatedName) {
			$results[] = array(
				'shortName'		=> $shortName, 
				'translatedName' 	=> $translatedName
			);
		}

		$result = array(
			'results'	=> $results
		);
		
		return $result;
	}
	
	function login() 
	{
		$username = $_REQUEST['username'];
		$password = $_REQUEST['password'];
		
		$egwBaseNamespace = new Zend_Session_Namespace('egwbase');
		
		$auth = new Egwbase_Auth_Sql();
		
		$auth->setIdentity($username)
			->setCredential($password);
			
		$result = $auth->authenticate();

		if ($result->isValid()) {
			$egwBaseNamespace->isAutenticated = TRUE;
			$egwBaseNamespace->currentAccount = $auth->getResultRowObject(NULL, array('account_pwd'));
		
			$response = array(
				'success'=> TRUE,
				'welcomeMessage' => "Some welcome message!"
			);
			
			$data = array(
				'sessionid'	=> session_id(),
				'loginid'	=> $egwBaseNamespace->currentAccount->account_lid,
				'ip'		=> $_SERVER['REMOTE_ADDR'],
				'account_id'	=> $egwBaseNamespace->currentAccount->account_id,
				'li'		=> time(),
				'result'	=> $result->getCode()
			);
			
			$accesslog = new Egwbase_Auth_Accesslog();
			$accesslog->insert($data);
			
		} else {
			$egwBaseNamespace->isAutenticated = FALSE;

			$response = array(
				'success'=> FALSE,
				'errorMessage' => "Wrong username or passord!"
			);
	
			$now = time();
			$data = array(
				'sessionid'	=> session_id(),
				'loginid'	=> $egwBaseNamespace->currentAccount->account_lid,
				'ip'		=> $_SERVER['REMOTE_ADDR'],
				'account_id'	=> $egwBaseNamespace->currentAccount->account_id,
				'li'		=> $now,
				'lo'		=> $now,
				'result'	=> $result->getCode()
			);
			
			$accesslog = new Egwbase_Auth_Accesslog();
			$accesslog->insert($data);
			sleep(2);
		}
		
		
		return $response;
	}

	function logout() 
	{
		Zend_Session::destroy();
		
		$result = array(
			'success'=> true,
		);
		
		return $result;
	}
}
?>