/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022-2023 Metaways Infosystems GmbH (http://www.metaways.de)
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

Tine.HumanResources.Model.FreeTimeTypeMixin = {
    statics: {
        getAbbreviation(freeTimeTypeData) {
            const app = Tine.Tinebase.appMgr.get('HumanResources');
            const name = app.i18n._hidden(freeTimeTypeData.name);
            return name.match(/.*\[(.+)\].*/)?.[1] || freeTimeTypeData.abbreviation;
        }
    },

    getTitle() {
        const app = Tine.Tinebase.appMgr.get('HumanResources');
        return app.i18n._hidden(this.get('name'));
    },

    getAbbreviation() {
        return Tine.HumanResources.Model.FreeTimeType.getAbbreviation(this.data);
    }
};
