<?php

$groupCtrl = Tinebase_Group::getInstance();
foreach ($groupCtrl->getMultiple($groupCtrl->getGroupMemberships($user->getId())) as $group) {
    if ('Users' === $group->name) {
        $state['Attributes']['Klasse'] = [$group->name];
        break;
    }
}
