<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      AirMike <airmike23@gmail.com>
 * @copyright   Copyright (c) 2012-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold data representing one node in the tree
 * 
 * @package     Filemanager
 * @subpackage  Model
 * @property    string             contenttype
 * @property    Tinebase_DateTime  creation_time
 * @property    string             hash
 * @property    string             name
 * @property    Tinebase_DateTime  last_modified_time
 * @property    string             object_id
 * @property    string             size
 * @property    string             type
 */
class Filemanager_Model_Node extends Tinebase_Model_Tree_Node
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Filemanager';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public static function inheritModelConfigHook(array &$_definition)
    {
        unset($_definition[self::VERSION]);
        $_definition[self::APP_NAME] = 'Filemanager';
        $_definition[self::MODEL_NAME] = 'modelName';
        
        parent::inheritModelConfigHook($_definition);
    }

    /**
     * returns true if this record should be replicated
     *
     * @return boolean
     */
    public function isReplicable()
    {
        return Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $this->type;
    }

    /**
     * returns a URL with a deep link path to the node provided
     *
     * @param Tinebase_Model_Tree_Node $_record
     * @return string
     */
    public static function getDeepLink($_record)
    {
        if (empty($_record->path)) {
            $path = Tinebase_Model_Tree_Node_Path::createFromStatPath(Tinebase_FileSystem::getInstance()->getPathOfNode($_record, true));
            $_record->path = $path->flatpath;
        }

        $path = explode('/', ltrim(Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($_record->path, 'Filemanager'), '/'));
        array_walk($path, function(&$val) {
            $val = urlencode($val);
        });

        return Tinebase_Core::getUrl() . '/#/Filemanager/showNode/' . join('/', $path);
    }
}
