<?php
/**
 * Calendar Event Notifications
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
?>
<?php if (count($this->updates) > 0): ?>
<?php echo $this->translate->_('Updates') ?>:
<?php 
foreach ($this->updates as $field => $update) {
    if ($field != 'attendee') {
        $i18nFieldName = Calendar_Model_Event::getTranslatedFieldName($field, $this->translate);
        $i18nOldValue  = Calendar_Model_Event::getTranslatedValue($field, $update, $this->translate, $this->timezone);
        $i18nCurrValue = Calendar_Model_Event::getTranslatedValue($field, $this->event->$field, $this->translate, $this->timezone);
        
        echo sprintf($this->translate->_('%1$s changed from "%2$s" to "%3$s"'), $i18nFieldName, $i18nOldValue, $i18nCurrValue) . "\n";
    }
}

if (array_key_exists('attendee', $this->updates)) {
    if (array_key_exists('toCreate', $this->updates['attendee'])) {
        foreach ($this->updates['attendee']['toCreate'] as $attender) {
            echo sprintf($this->translate->_('%1$s has been invited'), $attender->getName()) . "\n";
        }
    }
    if (array_key_exists('toDelete', $this->updates['attendee'])) {
        foreach ($this->updates['attendee']['toDelete'] as $attender) {
            echo sprintf($this->translate->_('%1$s has been removed'), $attender->getName()) . "\n";
        }
    }
    if (array_key_exists('toUpdate', $this->updates['attendee'])) {
        foreach ($this->updates['attendee']['toUpdate'] as $attender) {
            switch ($attender->status) {
                case Calendar_Model_Attender::STATUS_ACCEPTED:
                    echo sprintf($this->translate->_('%1$s accepted invitation'), $attender->getName()) . "\n";
                    break;
                    
                case Calendar_Model_Attender::STATUS_DECLINED:
                    echo sprintf($this->translate->_('%1$s declined invitation'), $attender->getName()) . "\n";
                    break;
                    
                case Calendar_Model_Attender::STATUS_TENTATIVE:
                    echo sprintf($this->translate->_('Tentative response from %1$s'), $attender->getName()) . "\n";
                    break;
                    
                case Calendar_Model_Attender::STATUS_NEEDSACTION:
                    echo sprintf($this->translate->_('No response from %1$s'), $attender->getName()) . "\n";
                    break;
            }
        }
    }
}
?>

<?php endif;?>
<?php echo $this->translate->_('Event details') ?>:
<?php 
$orderedFields = array('dtstart', 'dtend', 'summary', 'location', 'description',);

foreach($orderedFields as $field) {
    echo str_pad(Calendar_Model_Event::getTranslatedFieldName($field, $this->translate) . ':', 20) . 
         Calendar_Model_Event::getTranslatedValue($field, $this->event->$field, $this->translate, $this->timezone) . "\n";
}

echo $this->translate->plural('Attender', 'Attendee', count($this->event->attendee)). ":\n";
        
foreach ($this->event->attendee as $attender) {
    $status = $this->translate->_($attender->getStatusString());
    
    echo "    {$attender->getName()} ($status) \n";
}
?>

