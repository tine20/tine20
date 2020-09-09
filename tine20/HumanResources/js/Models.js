/*
* Tine 2.0
* 
* @package     HumanResources
* @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
* @author      Alexander Stintzing <a.stintzing@metaways.de>
* @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
*/
Ext.namespace('Tine.HumanResources.Model');
Tine.HumanResources.Model.FreeTimeMixin = {
    statics: {
        /**
         * prepares raw feastAndFreeDays response for client processing
         * 
         * @param feastAndFreeDays
         */
        prepareFeastAndFreeDays: function(feastAndFreeDays) {
            // sort freedays into freetime
            let allFreeDays = _.get(feastAndFreeDays, 'allFreeDays', []);
            let freeTimeTypes =  _.get(feastAndFreeDays, 'freeTimeTypes', []);
            _.each(_.get(feastAndFreeDays, 'allFreeTimes', []), (freeTime) => {
                _.set(freeTime, 'freedays', _.filter(allFreeDays, {freetime_id: freeTime.id}), []);
                _.set(freeTime, 'type', _.find(freeTimeTypes, {id: freeTime.type}));
            });    
        },
        
        /**
         *
         * @param {Array} feastAndFreeDaysCache
         * @param {Date} day
         */
        isExcludeDay(feastAndFreeDaysCache, day) {
            const feastAndFreeDays  = _.get(feastAndFreeDaysCache, day.format('Y'));
            const excludeDays = [].concat(
                _.get(feastAndFreeDays, 'excludeDates', []),
                _.get(feastAndFreeDays, 'feastDays', [])
            );
            
            return feastAndFreeDays ? !!_.find(excludeDays, {date: day.format('Y-m-d 00:00:00.000000')}) : false;
        },
        
        /**
         * get all FreeTimes of give day
         * 
         * @param {Array} feastAndFreeDaysCache
         * @param {Date|Date[]}day
         */
        getFreeTimes(feastAndFreeDaysCache, day) {
            return _.uniq(_.reduce(_.isArray(day) ? day : [day], (freeTimes, day) => {
                let feastAndFreeDays  = _.get(feastAndFreeDaysCache, day.format('Y'), []);
                let freeDayIds = _.map(_.filter(_.get(feastAndFreeDays, 'allFreeDays', []), {date: day.format('Y-m-d 00:00:00')}), 'freetime_id');
                return freeTimes.concat(_.filter(_.get(feastAndFreeDays, 'allFreeTimes', []), (freeTime) => {return _.indexOf(freeDayIds, freeTime.id) >= 0}));
            }, []));
        },
    }
};
