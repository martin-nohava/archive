// SPDX-FileCopyrightText: Martin Nohava <martin.nohava@vut.cz>
// SPDX-License-Identifier: AGPL-3.0-or-later
const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')

const appId = process.env.npm_package_name
webpackConfig.entry = {
    main: { import: path.join(__dirname, 'src', 'main.js'), filename: appId + '-' + 'main.js' },
	adminSettings: { import: path.join(__dirname, 'src', 'adminSettings.js'), filename: appId + '-' + 'adminSettings.js' },
    filesplugin: { import: path.join(__dirname, 'src', 'filesplugin.js'), filename: appId + '-' + 'filesplugin.js' },
}

module.exports = webpackConfig
