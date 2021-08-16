<?php
/**
 * Tine 2.0
 * @package     Courses
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * cli server for timetracker
 *
 * This class handles cli requests for the Courses app
 *
 * @package     Courses
 * @subpackage  Frontend
 */
class Courses_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Courses';
    
    /**
     * help array with function names and param descriptions
     */
    protected $_help = array(
    );

    public function resetCoursesMembersPrimaryGroups()
    {
        $this->_checkAdminRight();

        $groupCtrl = Tinebase_Group::getInstance();
        $defaultGroup = $groupCtrl->getDefaultGroup();
        $userCtrl = Tinebase_User::getInstance();

        foreach (Courses_Controller_Course::getInstance()->getAll() as $course) {
            try {
                if (!$course->group_id || $groupCtrl->getGroupById($course->group_id)) {
                    continue;
                }
            } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                continue;
            }

            /** @var Tinebase_Model_FullUser $user */
            foreach ($userCtrl->getMultiple($groupCtrl->getGroupMembers($course->group_id),
                    Tinebase_Model_FullUser::class) as $user) {
                if ($user->accountPrimaryGroup === $course->group_id) {
                    $groupCtrl->addGroupMember($defaultGroup->getId(), $user->getId());
                    $user->accountPrimaryGroup = $defaultGroup->getId();
                    $userCtrl->updateUser($user);
                }
            }
        }
    }

    /**
     * set all courses to internet = FILTERED
     * 
     * @return integer
     */
    public function resetCoursesInternetAccess()
    {
        $this->_checkAdminRight();

        $config = Courses_Config::getInstance();
        if (! isset($config->{Courses_Config::INTERNET_ACCESS_GROUP_ON}) || ! isset($config->{Courses_Config::INTERNET_ACCESS_GROUP_FILTERED})) {
            echo "No internet groups defined in config. Exiting\n";
            return 2;
        }
        
        $filter = new Courses_Model_CourseFilter(array(
            array('field' => 'internet', 'operator' => 'not', 'value' => 'FILTERED')
        ));
        
        $count = 0;
        
        foreach (Courses_Controller_Course::getInstance()->search($filter) as $course) {
            $course->internet = 'FILTERED';
            
            $group = Tinebase_Group::getInstance()->getGroupById($course->group_id);
            $group->members = Tinebase_Group::getInstance()->getGroupMembers($group);
            
            try {
                Courses_Controller_Course::getInstance()->saveCourseAndGroup($course, $group);
                $count++;
            } catch (Exception $e) {
                echo 'Failed to update course: ' . $course->name . PHP_EOL;
                echo $e . PHP_EOL;
            }
        }
        
        echo "Updated " . $count . " Course(s)\n";
        
        return 0;
    }
}
