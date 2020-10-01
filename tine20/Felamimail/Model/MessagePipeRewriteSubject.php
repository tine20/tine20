<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * felamimail model message pipe rewrite subject config model
 *
 * @package     Felamimail
 * @subpackage  Model
 *
 */
class Felamimail_Model_MessagePipeRewriteSubject implements Tinebase_BL_ElementInterface, Tinebase_BL_ElementConfigInterface
{
    protected $_config;

    public function __construct(array $config)
    {
        $this->_config = $config;
    }

    /**
     * copy mail with new subject / delete original message
     *
     * @param Tinebase_BL_PipeContext $_context
     * @param Tinebase_BL_DataInterface $_data
     */
    public function execute(Tinebase_BL_PipeContext $_context, Tinebase_BL_DataInterface $_data)
    {
        /** @var Felamimail_Model_Message $_data */

        if (preg_match($this->_config['pattern'], $_data->subject)) {
            $_data->subject = preg_replace($this->_config['pattern'], $this->_config['replacement'], $_data->subject);
        } else {
            return;
        }

        Felamimail_Controller_Message_Send::getInstance()->rewriteMessageSubject($_data,$_data->subject);
    }

    public function getNewBLElement()
    {
        return $this;
    }

    public function cmp(Tinebase_BL_ElementConfigInterface $_element)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' should not be called');
    }
}
