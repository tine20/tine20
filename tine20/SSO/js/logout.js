import waitFor from 'util/waitFor.es6';

(async () => {
    const initialData = await waitFor(() => { return window.initialData })

    const logoutRequests = []
    initialData.logoutUrls.forEach((logoutUrl, idx) => {
        logoutRequests.push(new Promise(async (resolve, reject) => {
            const frame = document.createElement('iframe')
            frame.src = logoutUrl
            frame.style.display = 'none'
            document.body.append(frame)
            try {
                const logoutStatus = await waitFor(() => { return frame?.contentWindow?.logoutStatus }, 5000)
                resolve(logoutStatus)
            } catch (e) {
                reject()
            }
        }))
    })

    const results = await Promise.allSettled(logoutRequests);
    const failures = results.reduce((failures, result, idx) => {
        if (! String(result?.value?.Code).match(/Success/)) {
            failures.push(new URL(window.initialData.logoutUrls[idx]).hostname)
        }
        return failures
    }, [])
    if (failures.length) {
        alert('Failed to logout from ' + failures.join(' and ') + '')
    }
    window.location = window.initialData.finalLocation
})();

