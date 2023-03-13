/**
 * Nextcloud - Archive
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 * 
 * @author Martin Nohava <martin.nohava@vut.cz>
 * @copyright Martin Nohava 2023
 * 
 * Code inspired by Mattermost integration into Nextcloud availble from
 * https://github.com/julien-nc/integration_mattermost by Julien Veyssier 2022
 */

import { generateUrl } from '@nextcloud/router'

export function gotoSettingsConfirmDialog() {
	const settingsLink = generateUrl('/settings/user/connected-accounts')
	OC.dialogs.message(
		t('integration_mattermost', 'You need to connect to a Mattermost server before using the Mattermost integration.')
		+ '<br><br>'
		+ t('integration_mattermost', 'Do you want to go to your "Connect accounts" personal settings?'),
		t('integration_mattermost', 'Connect to Mattermost'),
		'none',
		{
			type: OC.dialogs.YES_NO_BUTTONS,
			confirm: t('integration_mattermost', 'Go to settings'),
			confirmClasses: 'success',
			cancel: t('integration_mattermost', 'Cancel'),
		},
		(result) => {
			if (result) {
				window.location.replace(settingsLink)
			}
		},
		true,
		true,
	)
}

export function humanFileSize(bytes, approx = false, si = false, dp = 1) {
	const thresh = si ? 1000 : 1024

	if (Math.abs(bytes) < thresh) {
		return bytes + ' B'
	}

	const units = si
		? ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']
		: ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB']
	let u = -1
	const r = 10 ** dp

	do {
		bytes /= thresh
		++u
	} while (Math.round(Math.abs(bytes) * r) / r >= thresh && u < units.length - 1)

	if (approx) {
		return Math.floor(bytes) + ' ' + units[u]
	} else {
		return bytes.toFixed(dp) + ' ' + units[u]
	}
}