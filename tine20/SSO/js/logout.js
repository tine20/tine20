const ssoLogout = async (config) => {
    const keyPrefix = `${window.location.pathname}-sso-logout-client`
    let responses = []

    if(!config.logoutUrls) {
        // response from rp
        responses = JSON.parse(sessionStorage.getItem(`${keyPrefix}-responses`)) || []
        responses.push(config)

        config = JSON.parse(sessionStorage.getItem(`${keyPrefix}-config`))
    }

    // NOTE: binding GET can't be processed in background iframes
    //       @see 882ecda325 fix(SSO): cope with lax cookies in logout client
    if (config.logoutUrls?.get && config.logoutUrls.get.length) {
        const logoutUrl = config.logoutUrls.get.pop().url
        sessionStorage.setItem(`${keyPrefix}-config`, JSON.stringify(config))
        sessionStorage.setItem(`${keyPrefix}-responses`, JSON.stringify(responses))
        window.location = logoutUrl
        return
    }

    if (config.logoutUrls?.post && config.logoutUrls.post.length) {
        const {url, data} = config.logoutUrls.post.pop()
        sessionStorage.setItem(`${keyPrefix}-config`, JSON.stringify(config))
        sessionStorage.setItem(`${keyPrefix}-responses`, JSON.stringify(responses))
        document.body.innerHTML =
            `<body>` +
              `<p class="pulsate">logout from ${url}...</p>` +
              `<form method="post" action="${url}">` +
                Object.keys(data).map(key => {
                    return `<input type="hidden" name="${key}" value="${data[key]}"/>`
                }).join() +
                `<input type="submit" value="continue" style="display: none;"/>` +
              `</form>` +
              `<style>` +
                `.pulsate {animation: pulsate 1s ease-out; animation-iteration-count: infinite;}` +
                `@keyframes pulsate {0% { opacity: 0.5; } 50% { opacity: 1.0; } 100% { opacity: 0.5; }}` +
              `</style>` +
            `</body>`
        document.getElementsByTagName("form")[0].submit()
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
