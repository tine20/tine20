<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Tags
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * defines the datatype for one tag
 * 
 * @package     Tinebase
 * @subpackage  Tags
 */
class Tinebase_Model_Tag extends Tinebase_Record_Abstract
{
    /**
     * Type of a shared tag
     */
    const TYPE_SHARED = 'shared';
    /**
     * Type of a personal tag
     */
    const TYPE_PERSONAL = 'personal';
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        'name'              => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                     => array('Alnum', 'allowEmpty' => true),
        'type'                   => array(array('InArray', array(self::TYPE_PERSONAL, self::TYPE_SHARED))),
        'owner'                  => array('allowEmpty' => true),
        'name'                   => array('presence' => 'required'),
        'description'            => array('allowEmpty' => true),
        'color'                  => array('allowEmpty' => true, array('regex', '/^#[0-9a-fA-F]{6}$/')),
        'occurrence'             => array('allowEmpty' => true),
        'selection_occurrence'   => array('allowEmpty' => true), // not persistent
        'account_grants'         => array('allowEmpty' => true),
        'created_by'             => array('allowEmpty' => true),
        'creation_time'          => array('allowEmpty' => true),
        'last_modified_by'       => array('allowEmpty' => true),
        'last_modified_time'     => array('allowEmpty' => true),
        'is_deleted'             => array('allowEmpty' => true),
        'deleted_time'           => array('allowEmpty' => true),
        'deleted_by'             => array('allowEmpty' => true),
        'seq'                    => array('allowEmpty' => true),
    );
    
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time',
    );
    
    /**
     * returns containername
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
    
    /**
     * converts an array of tags names to a recordSet of Tinebase_Model_Tag
     * 
     * @param  iteratable           $tagNames
     * @param  bool                 $implicitAddMissingTags
     * @return Tinebase_Record_RecordSet
     */
    public static function resolveTagNameToTag($tagNames, $applicationName, $implicitAddMissingTags = true)
    {
        if (empty($tagNames)) {
            return new Tinebase_Record_RecordSet('Tinebase_Model_Tag');
        }
        
        $resolvedTags = array();
        
        foreach ((array)$tagNames as $tagName) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Trying to allocate tag ' . $tagName);
            
            $tagName = trim($tagName);
            if (empty($tagName)) {
                continue;
            }
            
            $existingTags = Tinebase_Tags::getInstance()->searchTags(
                new Tinebase_Model_TagFilter(array(
                    'name'        => $tagName,
                    'application' => $applicationName
                )), 
                new Tinebase_Model_Pagination(array(
                    'sort'    => 'type', // prefer shared over personal
                    'dir'     => 'DESC',
                    'limit'   => 1
                ))
            );
            
            if (count($existingTags) === 1) {
                //var_dump($existingTags->toArray());
                $resolvedTags[] = $existingTags->getFirstRecord();
        
            } elseif ($implicitAddMissingTags === true) {
                // No tag found, lets create a personal tag
                $resolvedTag = Tinebase_Tags::GetInstance()->createTag(new Tinebase_Model_Tag(array(
                    'type'        => Tinebase_Model_Tag::TYPE_PERSONAL,
                    'name'        => $tagName
                )));
                
                $resolvedTags[] = $resolvedTag;
            }
        }
        
        return new Tinebase_Record_RecordSet('Tinebase_Model_Tag', $resolvedTags);
    }
}
