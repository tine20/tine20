class ServerError extends Error {
    constructor(serverError) {
        super(serverError.message)
        this.code = serverError.code
        this.request = serverError.request
        this.response = serverError.response
        this.serverTrace = serverError.trace
        this.serverError = serverError
    }
}

export default ServerError
