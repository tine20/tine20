const expandFilter = (filterSpec, filter) => {
    if (filterSpec) {
        filter = filter || [];
        if (filterSpec.config) {
            filter = filter.concat(Tine.Tinebase.configManager.get(
                filterSpec.config.name, filterSpec.config.appName));
        }
        // @TODO: we might need a JSON.parse - implement when needed
        if (filterSpec.preference) {
            filter = filter.concat(
                Tine.Tinebase.appMgr.get(filterSpec.preference.appName)
                    .getRegistry().get('preferences').get(filterSpec.preference.name)
            );
        }
        if (filterSpec.favorite) {
            Tine.log.warn('Implement me');
        }
        return filter;
    }
};

export {
    expandFilter
}