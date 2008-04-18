<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @subpackage  Update
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id: Abstract.php 1013 2008-03-11 21:45:31Z nelius_weiss $
 */

/**
 * Common class for a Tine 2.0 Update
 * 
 * @package     Setup
 * @subpackage  Update
 */
class Setup_Update_Common
{
	public $backend;

	public function getApplicationVersion($_application)
	{
		$select = Zend_Registry::get('dbAdapter')->select()
				->from( SQL_TABLE_PREFIX . 'applications')
				->where('name = ?', $_application);

		$stmt = $select->query();
		$version = $stmt->fetchAll();
		
		return $version[0]['version'];
	}

	public function setApplicationVersion($_application, $_version)
	{
		$applicationsTable = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX . 'applications'));
		$where  = array(
                    $applicationsTable->getAdapter()->quoteInto('name = ?', $_application),
                );
		$result = $applicationsTable->update(array('version' => $_version), $where);
	}



}