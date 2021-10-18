<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Ching En Cheng <c.cheng@metaways.de>
 *
 */

/**
 * Confirmation exception
 *
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_Confirmation extends Tinebase_Exception_ProgramFlow
{
    /**
     * @var string _('Confirmation is required')
     */
    protected $_title = 'Confirmation is required';

    /**
     * extra info
     *
     * @var string
     */
    protected $_info = NULL;

    /**
     * the constructor
     *
     * @param string $_message
     * @param int $_code
     */
    public function __construct($_message, $_code = 650)
    {
        $translation = Tinebase_Translation::getTranslation();
        $this->_title = $translation->_($this->_title);
        // customized message
        parent::__construct($_message, $_code);
    }

    /**
     * set info
     *
     * @param string $_title
     */
    public function setTitle(string $_title)
    {
        $this->_title = $_title;
    }

    /**
     * set info
     *
     * @param string|null $_info
     */
    public function setInfo(string $_info = null)
    {
        $this->_info = $_info;
    }

    public function getInfo(): ?string
    {
        return $this->_info ?? null;
    }

    /**
     * returns info as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'info'     => $this->getInfo()
        ];
    }
}
