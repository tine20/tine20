<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Json interface to Tinebase
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Json
{
	
    /**
     * get list of translated country names
     *
     * @return array list of countrys
     */
    public function getCountryList()
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
     * returns tine translations of given application / (domain)
     * 
     * @param  string $application
     * @reutn  string
     */
    public function getTranslations($application)
    {
        header('Content-Type: text/html; charset=utf-8');
        
        $locale = (string)Zend_Registry::get('locale');
        if (file_exists(dirname(__FILE__) . "/../$application/translations/$locale.po")) {
            die(Tinebase_Translation::po2jsObject(dirname(__FILE__) . "/../$application/translations/$locale.po"));
        }
        $language = Zend_Registry::get('locale')->getLanguage();
        if (file_exists(dirname(__FILE__) . "/../$application/translations/$language.po")) {
            die(Tinebase_Translation::po2jsObject(dirname(__FILE__) . "/../$application/translations/$language.po"));
        }
    }
    
    public function getUsers($filter, $sort, $dir, $start, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Tinebase_User::getInstance()->getUsers($filter, $sort, $dir, $start, $limit)) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                //$result['totalcount'] = $backend->getCountByAddressbookId($addressbookId, $filter);
            }
        }
        
        return $result;
    }
    
    /**
     * get list of groups
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return array with results array & totalcount (int)
     */
    public function getGroups($filter, $sort, $dir, $start, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $groups = Tinebase_Group::getInstance()->getGroups($filter, $sort, $dir, $start, $limit);

        $result['results'] = $groups->toArray();
        $result['totalcount'] = count($groups);
        
        return $result;
    }
    
    /**
     * change password of user 
     *
     * @param string $oldPassword the old password
     * @param string $newPassword the new password
     * @return array
     */
    public function changePassword($oldPassword, $newPassword)
    {
        $response = array(
            'success'      => TRUE
        );
        
        try {
            Tinebase_Controller::getInstance()->changePassword($oldPassword, $newPassword, $newPassword);
        } catch (Exception $e) {
            $response = array(
                'success'      => FALSE,
                'errorMessage' => "new password could not be set!"
            );   
        }
        
        return $response;        
    }    
    
    /**
     * adds a new personal tag
     */
    public function saveTag($tag)
    {
        $tagData = Zend_Json::decode($tag);
        $inTag = new Tinebase_Tags_Model_Tag($tagData);
        
        if (strlen($inTag->getId()) < 40) {
            Zend_Registry::get('logger')->debug('creating tag: ' . print_r($inTag->toArray(), true));
            $outTag = Tinebase_Tags::getInstance()->createTag($inTag);
        } else {
            Zend_Registry::get('logger')->debug('updating tag: ' .print_r($inTag->toArray(), true));
            $outTag = Tinebase_Tags::getInstance()->updateTag($inTag);
        }
        return $outTag->toArray();
    }
    
    /**
     * gets tags for application / owners
     * 
     * @param   string  $context
     * @return array 
     * @deprecated ? use getTags or searchTags ?
     */
    public function getTags($context)
    {
        $filter = new Tinebase_Tags_Model_Filter(array(
            'name'        => '%',
            'application' => $context,
        ));
        $paging = new Tinebase_Model_Pagination();
        
        $tags = Tinebase_Tags::getInstance()->searchTags($filter, $paging)->toArray();
        return array(
            'results'    => $tags,
            'totalCount' => count($tags)
        );
    }
    
    /**
     * search tags
     *
     * @param string $query
     * @param string $context (application)
     * @param integer $start
     * @param integer $limit
     * @return array
     */
    public function searchTags($query, $context, $start=0, $limit=0)
    {
        $filter = new Tinebase_Tags_Model_Filter(array(
            'name'        => $query . '%',
            'application' => $context,
        ));
        $paging = new Tinebase_Model_Pagination(array(
            'start' => $start,
            'limit' => $limit,
            'sort'  => 'name',
            'dir'   => 'ASC'
        ));
        
        return array(
            'results'    => Tinebase_Tags::getInstance()->searchTags($filter, $paging)->toArray(),
            'totalCount' => Tinebase_Tags::getInstance()->getSearchTagsCount($filter)
        );
    }
    
    /**
     * search / get notes
     * - used by activities grid
     *
     * @param string $filter json encoded filter array
     * @param string $paging json encoded pagination info
     */
    public function searchNotes($filter, $paging)
    {
        $filter = new Tinebase_Notes_Model_NoteFilter(Zend_Json::decode($filter));
        $paging = new Tinebase_Model_Pagination(Zend_Json::decode($paging));
        
        //Zend_Registry::get('logger')->debug(print_r($filter->toArray(),true));
        //Zend_Registry::get('logger')->debug(print_r($paging->toArray(),true));
        
        return array(
            'results'       => Tinebase_Notes::getInstance()->searchNotes($filter, $paging)->toArray(),
            'totalcount'    => Tinebase_Notes::getInstance()->searchNotesCount($filter)
        );        
    }
    
    /**
     * get note types
     *
     * @todo add test
     */
    public function getNoteTypes()
    {
        $noteTypes = Tinebase_Notes::getInstance()->getNoteTypes()->toArray();
        
        return array(
            'results'       => $noteTypes,
            'totalcount'    => count($noteTypes)
        );        
    }
    
    /**
     * deletes tags identified by an array of identifiers
     * 
     * @param  array $ids
     * @return array 
     */
    public function deleteTags($ids)
    {
        Tinebase_Tags::getInstance()->deleteTags(Zend_Json::decode($ids));
        return array('success' => true);
    }
    

    /**
     * authenticate user by username and password
     *
     * @param string $username the username
     * @param string $password the password
     * @return array
     */
    public function login($username, $password)
    {
        if (Tinebase_Controller::getInstance()->login($username, $password, $_SERVER['REMOTE_ADDR']) === true) {
            $response = array(
				'success'       => TRUE,
                'account'       => Zend_Registry::get('currentAccount')->getPublicUser()->toArray(),
				'jsonKey'       => Zend_Registry::get('jsonKey'),
                'welcomeMessage' => "Welcome to Tine 2.0!"
			);
        } else {
            $response = array(
				'success'      => FALSE,
				'errorMessage' => "Wrong username or password!"
			);
        }

        return $response;
    }

    /**
     * destroy session
     *
     * @return array
     */
    public function logout()
    {
        Tinebase_Controller::getInstance()->logout($_SERVER['REMOTE_ADDR']);
        
        $result = array(
			'success'=> true,
        );

        return $result;
    }
}
