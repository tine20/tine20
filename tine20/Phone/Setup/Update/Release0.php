<?php
/**
 * Tine 2.0
 *
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class Phone_Setup_Update_Release0 extends Setup_Update_Common
{
    public function update_1()
    {
/*      $declaration = new Setup_Backend_Schema_Field();
        
        $declaration->name      = 'account_type';
        $declaration->type      = 'enum';
        $declaration->notnull   = 'true';
        $declaration->value     = array('anyone', 'account', 'group');
        
        $this->_backend->addCol('application_rights', $declaration);
        
        
        $declaration = new Setup_Backend_Schema_Field();
        
        $declaration->name      = 'right';
        $declaration->type      = 'text';
        $declaration->length    = 64;
        $declaration->notnull   = 'true';
        
        $this->_backend->alterCol('application_rights', $declaration);
        
        $this->setTableVersion('phone_extensions', '1');
        $this->setApplicationVersion('Phone', '0.2'); */
    }

    /**
     * rename application (Dialer -> Phone)
     *
     */
    public function update_2()
    {
        // rename database table
        $this->_backend->renameTable('dialer_extensions', 'phone_extensions');
        
        //-- rename application
        
        //$this->setApplicationVersion('Phone', '0.3');
    }    
}
