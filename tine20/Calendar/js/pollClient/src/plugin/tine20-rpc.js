var axios = require('axios')

var Tine20Rpc = {
  install (Vue, options) {
    if (typeof options.baseUrl === 'undefined') {
      options.baseUrl = window.location.protocol + '//' + window.location.host + '/'
    }

    Vue.prototype.$tine20 = {
      baseUrl: options.baseUrl,
      rpcCounter: 0,

      setJsonKey (key) {
        axios.defaults.headers.post['X-Tine20-JsonKey'] = key
      },

      request (method, params) {
        this.rpcCounter++
        let payload = {
          id: this.rpcCounter,
          jsonrpc: '2.0',
          method: method,
          params: params
        }

        var url = this.baseUrl + 'index.php'

        return new Promise(function (resolve, reject) {
          axios.post(url, payload).then(response => {
            if (typeof response.data.error !== 'undefined') {
              reject(response.data)
            }

            if (response.data.result.success) {
              axios.defaults.headers.post['X-Tine20-JsonKey'] = response.data.result.jsonKey
              resolve(response.data)
            } else {
              reject(response.data)
            }
          }).catch(error => {
            let statusText = 'An error occurred.'

            if (typeof error.message !== 'undefined') {
              statusText = error.message
            }

            if (typeof error.response !== 'undefined' && typeof error.response.statusText !== 'undefined') {
              statusText = 'HTTP Error: ' + error.response.statusText
            }

            error.result = {
              success: false,
              errorMessage: statusText
            }
            reject(error)
          })
        })
      }
    }
  }
}

module.exports = Tine20Rpc
