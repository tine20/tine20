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
 * Expressodriver_Model_NodePathFilter
 *
 * @package     Filamanager
 * @subpackage  Model
 *
 */
class Expressodriver_Model_NodePathFilter extends Tinebase_Model_Filter_Text
{

    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = Tinebase_Model_Filter_Text::toArray($_valueToJson);

        if ($this->_value === '/' || $this->_value === '') {
            $node = new Tinebase_Model_Tree_Node(array(
                'name' => 'root',
                'path' => '/',
                    ), TRUE);
        } else {
            $node = new Tinebase_Model_Tree_Node(array(
                'path' => $this->_value,
                'name' => 'nodeName',
                'object_id' => 1
            ));
        }
        $result['value'] = $node->toArray();

        return $result;
    }

    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
    );

    /**
     * the parsed path record
     *
     * @var Tinebase_Model_Tree_Node_Path
     */
    protected $_path = NULL;

    /**
     * @var array one of these grants must be met
     */
    protected $_requiredGrants = array(
        Tinebase_Model_Grants::GRANT_READ
    );

}
