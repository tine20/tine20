<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * class Tinebase_Model_Path
 * 
 * @package     Tinebase
 * @subpackage  Record
 *
 * @property string     $id
 * @property string     $path
 * @property string     $shadow_path
 */
class Tinebase_Model_Path extends Tinebase_Record_Abstract 
{
    public const TABLE_NAME = 'path';

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
        'recordName'        => 'Path',
        'recordsName'       => 'Paths', // ngettext('Path', 'Paths', n)
        'hasRelations'      => false,
        'copyRelations'     => false,
        'hasCustomFields'   => false,
        'hasNotes'          => false,
        'hasTags'           => false,
        'modlogActive'      => false,
        'hasAttachments'    => false,
        'createModule'      => false,
        'exposeHttpApi'     => false,
        'exposeJsonApi'     => false,

        'titleProperty'     => 'path',
        'appName'           => 'Tinebase',
        'modelName'         => 'Path',
        'table'             => array(
            'name'              => self::TABLE_NAME,
        ),

        'fields'            => [
            'path'              => [
                'validators'        => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'shadow_path'       => [
                'validators'        => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'creation_time'     => [
                'type'              => 'datetime',
                'validators'        => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
        ],
    ];

    /**
     * expects a shadow path part of format [] = optional, {} are part of the string!
     * [/]{MODELNAME}RECORDID
     *
     * returns array('parent' => {MODELNAME}RECORDID[{TYPE}], 'child' => [{TYPE}]/{MODELNAME}RECORDID)
     *
     * @param string $_shadowPathPart
     * @return array
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function getNeighbours($_shadowPathPart)
    {
        $shadowPathPart = trim($_shadowPathPart, '/');
        $pathParts = explode('/', ltrim($this->shadow_path, '/'));
        $parentPart = null;
        $childPart = null;
        $childPrefix = '';
        $match = false;

        foreach($pathParts as $pathPart) {
            if (false !== ($pos = strpos($pathPart, '{', 1))) {
                $type = substr($pathPart, $pos);
                $pathPart = substr($pathPart, 0, $pos);
            } else {
                $type = '';
            }
            if (true === $match) {
                $childPart .= $childPrefix . $pathPart;
                break;
            }
            if ($pathPart === $shadowPathPart) {
                $childPrefix = $type . '/';
                $match = true;
            } else {
                $parentPart = $pathPart . $type;
            }
        }

        if (false === $match) {
            throw new Tinebase_Exception_UnexpectedValue('trying to get path neighbours for a record that is not part of this path');
        }

        return array('parent' => $parentPart, 'child' => $childPart);
    }

    /**
     * @return array
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function getRecordIds()
    {
        $pathParts = explode('/', ltrim($this->shadow_path, '/'));
        $result = array();
        foreach($pathParts as $pathPart) {
            if (false !== ($pos = strpos($pathPart, '{', 1))) {
                $pathPart = substr($pathPart, 0, $pos);
            }

            if (false === ($pos = strpos($pathPart, '}'))) {
                throw new Tinebase_Exception_UnexpectedValue('malformed shadow path: ' . $this->shadow_path . ': working on path part: ' . $pathPart);
            }
            $model = substr($pathPart, 1, $pos - 1);
            $id = substr($pathPart, $pos + 1);
            $result[$pathPart] = array('id' => $id, 'model' => $model);
        }

        return $result;
    }

    public function getRecordIdsOfModel($_model)
    {
        $pathParts = explode('/', ltrim($this->shadow_path, '/'));
        $result = array();
        foreach($pathParts as $pathPart) {
            if (false !== ($pos = strpos($pathPart, '{', 1))) {
                $pathPart = substr($pathPart, 0, $pos);
            }

            if (false === ($pos = strpos($pathPart, '}'))) {
                throw new Tinebase_Exception_UnexpectedValue('malformed shadow path: ' . $this->shadow_path . ': working on path part: ' . $pathPart);
            }
            $model = substr($pathPart, 1, $pos - 1);
            if ($model !== $_model) {
                continue;
            }
            $result[] = substr($pathPart, $pos + 1);
        }

        return $result;
    }
}
