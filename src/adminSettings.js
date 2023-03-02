/**
 * Nextcloud - Archiv
 *
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Martin Nohava <martin.nohava@vut.cz>
 * @copyright Martin Nohava 2022
 */

import Vue from 'vue'
import AdminSettings from './components/AdminSettings.vue'

const VueAdminSettings = Vue.extend(AdminSettings)
new VueAdminSettings().$mount('#archive_prefs')