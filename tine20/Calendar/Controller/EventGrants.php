<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Calendar Grants Helper Class
 * 
 * @package Calendar
 * 
 * Dealing with grants in the Calendar application is a bit more complex than in 
 * most other Tine 2.0 apps, so we dedicate this job an own class.
 * 
 * In the calendar application, the container grants concept is slightly extended:
 *  1. GRANTS for events are not only based on the events "calendar" (technically 
 *     a container) but additionally a USER gets implicit grants for a event if 
 *     he is ATTENDER (+READ GRANT) or ORGANIZER (+READ,EDIT GRANT).
 *  2. ATTENDER which are invited to a certain "event" can assign the "event" to
 *     one of their personal calenders as "display calendar" (technically personal 
 *     containers they are admin of). The "display calendar" of an ATTENDER is
 *     stored in the attendee table.  Each USER has a default calendar, as 
 *     PREFERENCE,  all invitations are assigned to.
 *  3. The "effective GRANT" a USER has on a event (read/update/delete) is the 
 *     maximum GRANT of the following sources: 
 *      - container: GRANT the USER has to the calender of the event
 *      - implicit:  Additional READ GRANT for an attender and READ, EDIT GRANT
 *                   for the organizer.
 *      - inherited: GRANT the USER has to a the "display calendar" of an ATTENDER 
 *                   of the event, LIMITED by the maximum GRANT the ATTENDER has 
 *                   to the event. NOTE: that the ATTENDERS 'event' and _not_ 
 *                   'calendar' is important to also inherit implicit GRANTS.
 *  4. An Additional pseudo grant is the users PREFERENCE to grant all users to
 *     view his free/busy information.
 * 
 * When Applying/Asuring grants, we have to deal with two differnt situations:
 *  A: Check: Check individual grants on a event (record) basis.
 *            This is required for CRUD actions and done by the controllers 
 *            _checkGrant method.
 *  B: Seach: From the grants perspective this is a multy step process
 *            1. limiting the query (mixture of grants and filter)
 *            2. transform event set (all events user has only free/busy grant 
 *               for need to be cleaned)
 * 
 *  NOTE: To empower the client for enabling/disabling of actions based on the 
 *        grants a user has to an event, we need to compute the "effective GRANT"
 *        also for read/search operations
 *                  
 * Case A is not critical, as the amount of data is low and for CRUD operations
 * performace is less important. Case B however is the hard one, as lots of
 * calendars and events may be involved and performance is an issue.
 * 
 * As explained above, in the calendar application, asureing grants for search
 * operation is a multi step process clutterd over different places:
 *  - Calendar_Model_EventAclFilter -> filter for events having required grants
 *  - Calendar_Controller_Event -> getting effective grants and asureing CRUD grants
 * This class bundles the central data and logic needed from the different places.
 *
 * 
 * ---- @todo rework this section: this is solved on SQL time yet ---- 
 * Takeling the search problem generally:
 * 1. Step: filter by required grant (SQL) by combining grant sources:
 *  - free/busy: -> list of all calendars user has free/busy view grant for
 *  - container: -> easy, like in any normal container app
 *  - implicit:  -> join attendee and filter for user is organizer / attender
 *  - inherited: -> acts on displaycontainers, attendee and organizer
 *      -> NOTE: dammed complex, see bellow
 * 2. Step: calculate effective grants:
 *  - only read/update/delete are of interest for the client.
 *  - if all of them are already included by container and implicit source, we 
 *    can skip the complex inherited grants
 *  - if after all no grant is given, the user has only the grant to view free/
 *    busy information. The controller wipes off all information but dtstart, 
 *    dtend and the attendee the user has at least free/busy grants for
 * 
 * Takeling the inherited grants problem in search operations:
 *  @todo rethink this: the situation might be greatly simplefied when searching 
 *                      for read grant, as all atendee already have an implicit
 *                      read grant on the physical container!
 * 
 *  @todo rethink this: the problem only occous when searching for personal
 *                      containers,  user is not admin for (aka other users cals)
 *                      It only gets an performance problem if a LOT of those
 *                      containers are implied. Besides the planer view, this 
 *                      mostly are sense free searches.
 * 
 *  From the users perspective having a grant for a event depend on each entry
 *  of the 'inherited grant sources list' which for each user and grant contains:
 *  event.container_id, attender.user_id, attender.displaycalendar_id
 *  
 *  For each entry of this 'inherited grant sources list' the filter adds one where
 *  statement including the entries data. If required grant is read/update,
 *  each statment has an additional attendee/organizer part 
 *  
 *  This list could be assembled for a given user and a required grant by:
 *  1. get (display)container_ids user has required grant for
 *      -> NOTE this list might be limited by container a container filter
 *  2. do the following for each container in the list:
 *      2.1 get the admin(s) of the container
 *      2.2 get _all_ container_ids the admin(s) has required grant for
 *      2.3 add each of this containers to the 'inherited grant sources list'
 */
class Calendar_Controller_EventGrants
{
    
}