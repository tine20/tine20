<?php
/**
 * new lead plain view
 * 
 * @package     CRM
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      ?
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
?>
** <?php echo $this->lead->lead_name . " **\n" ?>

<?php echo $this->lead->lead_description ?>


<?php echo $this->lang_state ?>: <?php echo $this->leadState->lead_leadstate . "\n"?>
<?php echo $this->lang_type ?>: <?php echo $this->leadType->lead_leadtype . "\n" ?>
<?php echo $this->lang_source ?>: <?php echo $this->leadSource->lead_leadsource . "\n" ?>

<?php echo $this->lang_start ?>: <?php echo $this->leadStart . "\n" ?>
<?php echo $this->lang_scheduledEnd ?>: <?php echo $this->leadScheduledEnd . "\n" ?>
<?php echo $this->lang_end ?>: <?php echo $this->leadEnd . "\n" ?>

<?php echo $this->lang_turnover ?>: <?php echo $this->lead->lead_turnover . "\n" ?>
<?php echo $this->lang_probability ?>: <?php echo $this->lead->lead_probability . "%\n" ?>

<?php echo $this->lang_folder ?>: <?php echo $this->container->container_name . "\n" ?>

<?php echo $this->lang_updatedBy ?>: <?php echo $this->updater->accountDisplayName . "\n" ?>