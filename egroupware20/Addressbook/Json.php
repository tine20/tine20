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
     * @param string $nodeid
     * @param string $_datatype
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @param bool $displayContacts
     * @param bool $displayLists
     * @param string $options json encoded array of additional options
     * @return array
     */
    public function getContacts($nodeid, $datatype, $start, $sort, $dir, $limit, $displayContacts = TRUE, $displayLists = TRUE, $options = NULL)
    {
        $result = array();
        switch($datatype) {
            case 'accounts':
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                if($rows = $backend->getInternalContacts(NULL, $sort, $dir, $limit, $start)) {
                    $result['results'] = $rows->toArray();
                    $result['totalcount'] = $backend->getInternalCount();
                }
                
                break;

            case 'mycontacts':
                $options = Zend_Json::decode($options);
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                if($rows = $backend->getPersonalContacts(NULL, $options, $sort, $dir, $limit, $start)) {
                    $result['results'] = $rows->toArray();
                    $result['totalcount'] = $backend->getPersonalCount();
                }
                
                break;

            case 'mylist':
            	$options = Zend_Json::decode($options);
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                if($rows = $backend->getPersonalList($options['listId'], NULL, $sort, $dir, $limit, $start)) {
                    $result['results'] = $rows->toArray();
                    $result['totalcount'] = $backend->getPersonalCount();
                }
                
                break;

            default:
                $contacts = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                if($rows = $contacts->fetchAll(NULL, "$sort $dir", $limit, $start)) {
                    $result['results'] = $rows->toArray();
                    $result['totalcount'] = $contacts->getTotalCount();
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
        $treeNode = new Egwbase_Ext_Treenode('Addressbook', 'overview', 'addressbook', 'Addressbook', FALSE);
        $treeNode->setIcon('apps/kaddressbook.png');
        $treeNode->cls = 'treemain';

        $childNode = new Egwbase_Ext_Treenode('Addressbook', 'mycontacts', 'mycontacts', 'My Contacts', FALSE);
        $treeNode->addChildren($childNode);
        
        $childNode = new Egwbase_Ext_Treenode('Addressbook', 'accounts', 'accounts', 'All Users', TRUE);
        $treeNode->addChildren($childNode);
        
        $childNode = new Egwbase_Ext_Treenode('Addressbook', 'otherpeople', 'otherpeople', 'Other Users Contacts', FALSE);
        $treeNode->addChildren($childNode);
        
        $childNode = new Egwbase_Ext_Treenode('Addressbook', 'sharedaddressbooks', 'sharedaddressbooks', 'Shared Contacts', FALSE);
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
    public function getTree($node, $datatype)
    {
        $nodes = array();
        
        switch($node) {
            case 'mycontacts':
                $backend = Addressbook_Backend::factory(Addressbook_Backend::SQL);
                $personalLists = $backend->getPersonalLists();
                foreach($personalLists as $listObject) {
                    $treeNode = new Egwbase_Ext_Treenode(
                        'Addressbook',
                        'mylist',
                        'mylist-'. $listObject->list_id, 
                        $listObject->list_name,
                        TRUE
                    );
                    $treeNode->contextMenuClass = 'ctxMenuMyList';
                    $treeNode->listId = $listObject->list_id;
                    $nodes[] = $treeNode;
                }
		
                break;
        }
        echo Zend_Json::encode($nodes); 
        
        // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
        exit;
    }
}