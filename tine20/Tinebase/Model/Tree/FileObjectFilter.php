<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2016-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 *  file object filter class
 *
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Tree_FileObjectFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';

    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = Tinebase_Model_Tree_FileObject::class;

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = [
        'is_deleted'            => ['filter' => Tinebase_Model_Filter_Bool::class],
        'type'                  => ['filter' => Tinebase_Model_Filter_Text::class],
        'size'                  => ['filter' => Tinebase_Model_Filter_Int::class, 'options' => ['tablename' => 'tree_filerevisions']]
    ];
}
