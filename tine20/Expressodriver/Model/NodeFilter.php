<?php
/**
 * Tine 2.0
 *
 * @package     Expressodriver
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 */

/**
 * tree node filter class for Expressodriver
 *
 * @package     Expressodriver
 * @subpackage  Model
 */
class Expressodriver_Model_NodeFilter extends Tinebase_Model_Tree_Node_Filter
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Expressodriver_Model_NodeFilter';

    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Expressodriver';

    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Tinebase_Model_Tree_Node';

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'                => array(
            'filter' => 'Tinebase_Model_Filter_Query',
            'options' => array('fields' => array('name'))
        ),
        'id'                   => array('filter' => 'Tinebase_Model_Filter_Id'),
        'path'                 => array('filter' => 'Expressodriver_Model_NodePathFilter'),
        'parent_id'            => array('filter' => 'Tinebase_Model_Filter_Text'),
        'name'                 => array('filter' => 'Tinebase_Model_Filter_Text'),
        'object_id'            => array('filter' => 'Tinebase_Model_Filter_Text'),

        'last_modified_time'   => array('filter' => 'Tinebase_Model_Filter_Date'),
        'deleted_time'         => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'creation_time'        => array('filter' => 'Tinebase_Model_Filter_Date'),
        'last_modified_by'     => array('filter' => 'Tinebase_Model_Filter_User'),
        'created_by'           => array('filter' => 'Tinebase_Model_Filter_User'),
        'type'                 => array('filter' => 'Tinebase_Model_Filter_Text'),
        'contenttype'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'description'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'size'                 => array('filter' => 'Tinebase_Model_Filter_Int'),

        'recursive' => array('filter' => 'Tinebase_Model_Filter_Bool')
    );
}
