<?php
/**
 * Tine 2.0
 *
 * @package     Expressodriver
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 * @todo        extend Expressodriver_Exception
 */

/**
 * NodeExists exception
 *
 * @package     Expressodriver
 * @subpackage  Exception
 */
class Expressodriver_Exception_NodeExists extends Expressodriver_Exception
{
    /**
     * existing nodes info
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_existingNodes = NULL;

    /**
     * construct
     *
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'file exists', $_code = 901)
    {
        $this->_existingNodes = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');

        parent::__construct($_message, $_code);
    }

    /**
     * set existing nodes info
     *
     * @param Tinebase_Record_RecordSet $_existingNode
     */
    public function addExistingNodeInfo(Tinebase_Model_Tree_Node $_existingNode)
    {
        $this->_existingNodes->addRecord($_existingNode);
    }

    /**
     * get existing nodes info
     *
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function getExistingNodesInfo()
    {
        return $this->_existingNodes;
    }

    /**
     * returns existing nodes info as array
     *
     * @return array
     */
    public function toArray()
    {
        $this->getExistingNodesInfo()->setTimezone(Tinebase_Core::getUserTimezone());
        return array(
            'existingnodesinfo' => $this->_existingNodes->toArray()
        );
    }
}
