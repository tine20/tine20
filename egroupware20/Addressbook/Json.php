<?php
/**
 * backend class for Zend_Json_Server
 * 
 * This class handles all Json requests for the addressbook application
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Addressbook_Json
{
    /**
     * delete a array of contacts
     *
     * @param array $_contactIDs
     * @return array
     */
    public function deleteAddress($_contactIDs)
    {
        $contactIDs = Zend_Json::decode($_contactIDs);
        if(is_array($contactIDs)) {
            $contacts = Addressbook_Backend::factory(Addressbook_Backend::SQL);
            $contacts->deletePersonalContacts($contactIDs);

            $result = array('success'   => TRUE, 'ids' => $contactIDs);
        } else {
            $result = array('success'   => FALSE);
        }
        
        return $result;
    }
    
    /**
     * read one contact
     *
     * @param int $_contactID
     * @return array
     */
    public function readAddress($_contactID)
    {
        $addresses = new Addressbook_Addresses();
        if($rows = $addresses->find($_contactID)) {
            $result['results'] = $rows->toArray();
        }
        
        return $result;
    }
	
    /**
     * save one contact
     * 
     * if $_contactID is NULL the contact gets added, otherwise it gets updated
     *
     * @param int $_contactID
     * @return array
     */
    public function saveAddress($_contactID = NULL)
    {
        $input = new Zend_Filter_Input(Addressbook_Addresses::getFilter(), Addressbook_Addresses::getValidator(), $_POST);
        
        if ($input->isValid()) {
            $address = new Addressbook_Addresses();
            
            $data = $input->getUnescaped();
            if(isset($data['contact_bday'])) {
                $locale = Zend_Registry::get('locale');
                $dateFormat = $locale->getTranslationList('Dateformat');
                // convert bday back to yyyy-mm-dd
                try {
                    $date = new Zend_Date($data['contact_bday'], $dateFormat['long'], 'en');
                    $data['contact_bday'] = $date->toString('yyyy-MM-dd');
                } catch (Exception $e) {
                    unset($data['contact_bday']);
                }
            }
            
            if($_contactID > 0) {
                try {
                    $where = $address->getAdapter()->quoteInto('contact_id = ?', (int)$_contactID);
                    $address->update($data, $where);
                    $result = array('success'           => true,
                                    'welcomeMessage'    => 'Entry updated');
                } catch (Exception $e) {
                    $result = array('success'           => false,
                                    'errorMessage'      => $e->getMessage());
                }
            } else {
                try {
                    $address->insert($data);
                    $result = array('success'           => true,
                                    'welcomeMessage'    => 'Entry saved');
                } catch (Exception $e) {
                    $result = array('success'           => false,
                                    'errorMessage'      => $e->getMessage());
                }
            }
        } else {
            foreach($input->getMessages() as $fieldName => $errorMessages) {
                $errors[] = array('id'  => $fieldName,
                                  'msg' => $errorMessages[0]);
            }
            
            $result = array('success'           => false,
                            'errors'            => $errors,
                            'errorMessage'      => 'filter NOT ok');
        }
        
        return $result;
    }
    
    /**
     * get data for overview
     * 
     * returns the data to be displayed in a ExtJS grid
     *
     * @todo implement correc total count for lists
     * @param string $nodeid
     * @param string $_datatype
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @param string $options json encoded array of additional options
     * @return array
     */
    public function getContacts($nodeid, $datatype, $owner, $start, $sort, $dir, $limit, $options = NULL)
    {
        $result = array();
        switch($datatype) {
            case 'accounts':
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                if($rows = $backend->getAccounts(NULL, $sort, $dir, $limit, $start)) {
                    $result['results']    = $rows->toArray();
                    $result['totalcount'] = $backend->getCountOfAccounts();
                }
                
                break;

            case 'contacts':
                $options = Zend_Json::decode($options);
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                if($rows = $backend->getContactsByOwner($owner, NULL, $options, $sort, $dir, $limit, $start)) {
                    $result['results']    = $rows->toArray();
                    $result['totalcount'] = $backend->getCountByOwner($owner);
                }
                
                break;

            case 'list':
            	$options = Zend_Json::decode($options);
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                $listId = $options['listId'];
                error_log("$listId, $owner, NULL, $sort, $dir, $limit, $start");
                if($rows = $backend->getContactsByList($options['listId'], $owner, NULL, $sort, $dir, $limit, $start)) {
                    $result['results']    = $rows->toArray();
                    $result['totalcount'] = $backend->getCountByOwner($owner);
                }
                
                break;

            case 'otherpeople':
				$options = Zend_Json::decode($options);
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
				
//				$nodes = json_decode($this->getTree('otherpeople', $owner, 'otherpeople'));
				$nodes = array('0' => array('owner' => '7'), '1' => array('owner' => '-5'));

				foreach($nodes as $node)
				{		
	                if($rows = $backend->getContactsByOwner($node['owner'], NULL, $options, $sort, $dir, $limit, $start)) {
	                    $result['results']    .= $rows->toArray();
	                    $result['totalcount'] = $result['totalcount'] + $backend->getCountByOwner($options['ownerId']);
	                }
                }
                break;
		
            case 'sharedaddressbooks':
            	$options = Zend_Json::decode($options);
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                $listId = $options['listId'];
                error_log("$listId, $owner, NULL, $sort, $dir, $limit, $start");
                if($rows = $backend->getContactsByList($options['listId'], $owner, NULL, $sort, $dir, $limit, $start)) {
                    $result['results']    = $rows->toArray();
                    $result['totalcount'] = $backend->getCountByOwner($owner);
                }
                
                break;				

        }
        
        return $result;
    }
    
    /**
     * Returns the structure of the initial tree for this application.
     * 
     * This function returns the needed structure, to display the initial tree, after the the logoin.
     * Additional tree items get loaded on demand.
     *
     * @return array
     */
    public function getMainTree()
    {
        $currentAccount = Zend_Registry::get('currentAccount');
        
        $treeNode = new Egwbase_Ext_Treenode('Addressbook', 'overview', 'addressbook', 'Addressbook', FALSE);
        $treeNode->setIcon('apps/kaddressbook.png');
        $treeNode->cls = 'treemain';

        $childNode = new Egwbase_Ext_Treenode('Addressbook', 'contacts', 'mycontacts', 'My Contacts', FALSE);
        $childNode->owner = $currentAccount->account_id; 
        $treeNode->addChildren($childNode);
        
        $childNode = new Egwbase_Ext_Treenode('Addressbook', 'accounts', 'accounts', 'All Users', TRUE);
        $childNode->owner = 0; 
        $treeNode->addChildren($childNode);
        
        $childNode = new Egwbase_Ext_Treenode('Addressbook', 'otherpeople', 'otherpeople', 'Other Users Contacts', FALSE);
        $childNode->owner = 0; 
        $treeNode->addChildren($childNode);
        
        $childNode = new Egwbase_Ext_Treenode('Addressbook', 'sharedaddressbooks', 'sharedaddressbooks', 'Shared Contacts', FALSE);
        $childNode->owner = 0; 
        $treeNode->addChildren($childNode);
        
        return $treeNode;
    }
    
    /**
     * returns the nodes for the dynamic tree
     *
     * @param string $node which node got selected in the UI
     * @param string $datatype what kind of data to search
     * @return string json encoded array
     */
    public function getTree($node, $owner, $datatype)
    {
        $nodes = array();
        
        switch($datatype) {
            case 'contacts':
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                $lists = $backend->getListsByOwner($owner);
                foreach($lists as $listObject) {
                    $treeNode = new Egwbase_Ext_Treenode(
                        'Addressbook',
                        'list',
                        'list-'. $listObject->list_id, 
                        $listObject->list_name,
                        TRUE
                    );
                    $treeNode->contextMenuClass = 'ctxMenuList';
                    $treeNode->listId = $listObject->list_id;
                    $treeNode->owner  = $owner;
                    $nodes[] = $treeNode;
                }
		
                break;
                
            case 'otherpeople':
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                $lists = $backend->getOtherAddressbooks();
                foreach($lists as $listObject) {
                    $treeNode = new Egwbase_Ext_Treenode(
                        'Addressbook',
                        'contacts',
                        'other_'. $listObject->id, 
                        $listObject->title,
                        FALSE
                    );
                    $treeNode->contextMenuClass = 'ctxMenuContacts';
                    $treeNode->owner  = $listObject->id;
                    $nodes[] = $treeNode;
                }
                
                break;

            case 'sharedaddressbooks':
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                $lists = $backend->getSharedAddressbooks();
                foreach($lists as $listObject) {
                    $treeNode = new Egwbase_Ext_Treenode(
                        'Addressbook',
                        'contacts',
                        'shared_'. $listObject->id, 
                        $listObject->title,
                        FALSE
                    );
                    $treeNode->contextMenuClass = 'ctxMenuContacts';
                    $treeNode->owner  = $listObject->id;
                    $nodes[] = $treeNode;
                }
                
                break;
        }
        echo Zend_Json::encode($nodes); 
        
        // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
        exit;
    }
}