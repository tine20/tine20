import waitFor from 'util/waitFor.es6';
import ssoLogout from './logout'

(async () => {
    const initialData = await waitFor(() => { return window.initialData })

    ssoLogout(initialData.logoutUrls)
    window.location = window.initialData.finalLocation
})();
