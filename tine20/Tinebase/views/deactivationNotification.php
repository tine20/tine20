<?php
/**
 * User deactivation notification
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
?>
<?php echo $this->translate->_('Your user account has been deactivated') . "\n" . "\n" ?>
<?php echo $this->translate->_('Username') . ': ' . $this->accountLoginName  . "\n" ?>
<?php /* echo $this->translate->_('Deactivation date') . ': ' . $this->??? needs to be added   . "\n"*/ ?>
<?php echo $this->translate->_('Tine 2.0 URL') . ': ' . $this->tine20Url  . "\n" ?>
