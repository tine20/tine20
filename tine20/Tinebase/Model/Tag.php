<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Tags
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * defines the datatype for one tag
 * 
 * @package     Tinebase
 * @subpackage  Tags
 *
 * @property string $id
 * @property string $name
 * @property boolean $system_tag
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
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        'recordName'        => 'Tag',
        'recordsName'       => 'Tags', // ngettext('Tag', 'Tags', n)
        'hasRelations'      => false,
        'hasCustomFields'   => false,
        'hasNotes'          => false,
        'hasTags'           => false,
        'hasXProps'         => false,
        'modlogActive'      => true,
        'hasAttachments'    => false,
        'createModule'      => false,
        'exposeHttpApi'     => false,
        'exposeJsonApi'     => false,
        self::TITLE_PROPERTY => 'name',

        'appName'           => 'Tinebase',
        'modelName'         => 'Tag',

        'filterModel'       => [],

        'fields'            => [
            'type'                          => [
                'type'                          => 'string',
                'validators'                    => [
                    'inArray' => [
                        self::TYPE_PERSONAL,
                        self::TYPE_SHARED,
                    ]
                ],
            ],
            'owner'                         => [
                //'type'                          => 'record',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'name'                          => [
                'type'                          => 'string',
                'validators'                    => ['presence' => 'required'],
                'inputFilters'                  => [Zend_Filter_StringTrim::class => null],
            ],
            'description'                   => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'color'                         => [
                'type'                          => 'string',
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    ['regex', '/^#[0-9a-fA-F]{6}$/'],
                ],
            ],
            'system_tag'                    => [
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL               => 0,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'occurrence'                    => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'selection_occurrence'          => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'account_grants'                => [
                //'type'                          => '!?',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'rights'                        => [
                //'type'                          => '!?',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'contexts'                      => [
                //'type'                          => '!?',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
        ],
    ];
    
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
     * @param  array    $tagNames
     * @param  string   $applicationName
     * @param  bool     $implicitAddMissingTags
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
