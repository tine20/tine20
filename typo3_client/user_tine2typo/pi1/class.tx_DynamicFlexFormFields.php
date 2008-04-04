<?php

////require_once( PATH_site . '/../scripts/Factory.php' );
//require_once( 'Factory.php' );

require_once(PATH_tslib.'class.tslib_pibase.php');
		
class tx_DynamicFlexFormFields extends tslib_pibase 
{
    
    /**

	/*public function getXML( $config )
	{
	
		try
		{
			$dbh = Factory::createDB( 'PDO' );

			$sql = '
				SELECT pi_flexform
				FROM nml_category AS n, nml_category AS p
				WHERE n.nlc_left > 1 AND n.nlc_left BETWEEN p.nlc_left AND p.nlc_right
				GROUP BY n.nlc_id
				ORDER BY n.nlc_left
			';

			$stmt = $dbh->prepare( $sql );
			$stmt->execute();

			while( ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ) !== false )
			{
				$config['items'][] = array( $row['nlc_name'], $row['nlc_id'] );
			}			
			
			$dbh = null;
		}
		catch( PDOException $pe ) {
			error_log( 'user_M001NewsTeaser/tx_DynamicFlexFormFields::addCategories(): ' . $pe->getMessage() . ' - ' . $pe->errorInfo );
		}
		catch( Exception $e ) {
			error_log( 'user_M001NewsTeaser/tx_DynamicFlexFormFields::addCategories(): ' . $e->getMessage() );
		}

		return $config;
	}




*/

	var $prefixId = 'tx_DynamicFlexFormFields';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_DynamicFlexFormFields.php';	// Path to this script relative to the extension dir.
	var $extKey = 'user_tine2typo';	// The extension key.
//	var $languageFile = ;

	function getNames( $config )
	{
	//var_dump($this->getXML);
	//echo $config

		$bert = $config * 1 ;
		$pferd[] = "gallo";
		//$pferd['items'][0] = $bert;
		$config['items'] = array(array(0 => $bert) , array('bier') ,array('brot'),array('wein'));
		#$xyz = array();
//		$
			
		//$this->pi_setPiVarDefaults();
		//$this->pi_loadLL();
		//$this->pi_USER_INT_obj = 1;	
		//$this->pi_initPIflexForm();
	//var_dump($this);
/*
				$path = array(
					get_include_path(),
					PATH_site . 'typo3conf/ext/user_tine2typo/pi1/',
				);
	
				set_include_path(implode(PATH_SEPARATOR, $path));

				// zend auto loader will load any classes - can not use it
											
				require_once( 'TineClient/Connection.php');
				require_once( 'TineClient/Service/Abstract.php');
				require_once( 'Zend/Http/Client.php');
				require_once( 'Addressbook/Model/Contact.php');
				require_once( 'Addressbook/Service.php');
				
				try
				{
					// open connection
					$client = new TineClient_Connection($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tinehost'));
					//$client->setDebugEnabled(true);
					TineClient_Service_Abstract::setDefaultConnection($client);

					// login to tine2.0
					$client->login(
								$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tinehostlogin'), 
								$this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tinehostpassword')
								);
				}
				catch (Exception $e) 
				{
					echo "can not login";
					//var_dump($e);
					//exit;
				}
				try 
				{
					// write addressbook entry
				 
					//$contact = new Addressbook_Model_Contact($contactData);
					$addressbook = new Addressbook_Service();
					$Contact = $addressbook->getContact(38);
	
				}
				catch (Exception $e) 
				{
					var_dump($e);
					exit;
				}
				
				try
				{
					$client->logout();
				}
				catch (Exception $e) 
				{
					var_dump($e);
				
				}
				$config['items'][] = $Contact;
	/*0
		try
		{
			$dbh = Factory::createDB( 'PDO' );

			$sql = '
				SELECT n.nlc_id, CONCAT( REPEAT(\'. \', COUNT(p.nlc_name) - 1), n.nlc_name) AS nlc_name
				FROM nml_category AS n, nml_category AS p
				WHERE n.nlc_left > 1 AND n.nlc_left BETWEEN p.nlc_left AND p.nlc_right
				GROUP BY n.nlc_id
				ORDER BY n.nlc_left
			';

			$stmt = $dbh->prepare( $sql );
			$stmt->execute();

			while( ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ) !== false )
			{
				$config['items'][] = array( $row['nlc_name'], $row['nlc_id'] );
			}			
			
			$dbh = null;
		}
		catch( PDOException $pe ) {
			error_log( 'user_M001NewsTeaser/tx_DynamicFlexFormFields::addCategories(): ' . $pe->getMessage() . ' - ' . $pe->errorInfo );
		}
		catch( Exception $e ) {
			error_log( 'user_M001NewsTeaser/tx_DynamicFlexFormFields::addCategories(): ' . $e->getMessage() );
		}
		
		
		
*/
		return $config;
	}


	
}

?>