const ssoLogout = async (config) => {
    const keyPrefix = `${window.location.pathname}-sso-logout-client`
    let responses = []

    if(!config.logoutUrls) {
        // response from rp
        responses = JSON.parse(sessionStorage.getItem(`${keyPrefix}-responses`)) || []
        responses.push(config)

        config = JSON.parse(sessionStorage.getItem(`${keyPrefix}-config`))
    }

    if (config.logoutUrls.length) {
        const logoutUrl = config.logoutUrls.pop()
        sessionStorage.setItem(`${keyPrefix}-config`, JSON.stringify(config))
        sessionStorage.setItem(`${keyPrefix}-responses`, JSON.stringify(responses))
        window.location = logoutUrl
        return
    }
    
    const failures = responses.reduce((failures, response, idx) => {
        if (! String(response.logoutStatus?.value?.Code).match(/Success/)) {
            failures.push(response.relyingParty.label)
        }
        return failures
    }, [])

    if (errors.length) {
        alert('Failed to logout from ' + failures.join(' and ') + '')
    }

    sessionStorage.removeItem(`${keyPrefix}-config`)
    sessionStorage.removeItem(`${keyPrefix}-responses`)

    window.location = config.finalLocation 
}

export { ssoLogout }
