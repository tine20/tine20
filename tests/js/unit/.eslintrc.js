// http://eslint.org/docs/user-guide/configuring
const merge = require('./../../../tine20/Tinebase/js/node_modules/webpack-merge');
const common = require('./../../../tine20/.eslintrc.js');

module.exports = merge(common, {
  env: {
    browser: true,
    mocha: true
  },
    // globals: ['expect', 'sinon'],
  globals: {
    sinon: true,
    expect: true
  },
  plugins: [
    'mocha', 'chai-expect', 'chai-friendly', 'sinon'
  ],
  // add your custom rules here
  'rules': {
    'mocha/no-exclusive-tests': 'error',
    'no-unused-expressions': 0,
    'chai-friendly/no-unused-expressions': 2
    // 'sinon/no-fakes': 2
  }
})
