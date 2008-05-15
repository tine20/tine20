<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class Addressbook_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * this function does nothing. It's from the dark ages without setup being functional
     */    
    public function update_1()
    {
        $this->validateTableVersion('addressbook', '1');        
        
        $this->setApplicationVersion('Addressbook', '0.2');
    }
    
    /**
     * updates what???
     * 
     * @todo add changed fields
     */    
    public function update_2()
    {
        $this->validateTableVersion('addressbook', '1');        
        
        $this->setTableVersion('addressbook', '2');
        $this->setApplicationVersion('Addressbook', '0.3');
    }
}