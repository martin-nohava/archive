<template>
    <!--
    SPDX-FileCopyrightText: Martin Nohava <martin.nohava@vut.cz>
    SPDX-License-Identifier: AGPL-3.0-or-later
    -->
    <div>
        <NcSettingsSection
            title="Connect to the Archive server"
            description="Nextcloud Archive requires a separate server to which selected files are moved and securely archived. To enable archiving, you must first connect to the server."
            doc-url="https://github.com/martin-nohava/archive"
            :limit-width="true">

            <NcLoadingIcon v-if="checkingConnection" :size="64" title="Checking connection to server..." />

            <div v-else>
                <NcNoteCard type="warning" v-if="state.url === ''">
                <p>{{ t('archive', 'Connection unconfigured') }}</p>
                </NcNoteCard>

                <NcNoteCard type="success" v-else-if="connected">
                    <p>{{ t('archive', 'Connected') }}</p>
                </NcNoteCard>

                <NcNoteCard type="error" heading="Connection Error" v-else>
                    <p>{{ t('archive', 'Server is unreachable') }}</p>
                </NcNoteCard>
            </div>

            <span class="field-label">
                <ConnectionIcon />
                <span>
                    <strong>
                        {{ t('archive', 'Connection') }}
                    </strong>
                </span>
			</span>
            <NcTextField :value.sync="state.url"
                :label="t('archive', 'URL (or IP) address and port number of Archive server')"
                placeholder="https://example.domain:port"
                trailing-button-icon="close"
                :show-trailing-button="state.url !== ''"
                @trailing-button-click="clearUrl"
                :label-visible="true">
                <WebIcon :size="16"/>
            </NcTextField>

            <NcPasswordField :value.sync="state.secret"
                :label="t('archive', 'Secret')"
                placeholder="This secret is configured on Archive server"
                :label-visible="true">
                <LockIcon :size="16" />
            </NcPasswordField>

            <div class="toggle-container">
                <NcCheckboxRadioSwitch :checked.sync="state.selfsigned">{{ t('archive', 'Allow self signed certificates') }}</NcCheckboxRadioSwitch>
            </div>
            <div class="warning-container" v-if="state.selfsigned">
                <AlertShieldIcon class="warning-icon" />
					<label>
						{{t('archive', 'Trusting all certificates, connection might be insecure!')}}
					</label>
            </div>
            <div class="warning-container" v-if="!urlIsSecure">
                <AlertShieldIcon class="warning-icon" />
					<label>
						{{t('archive', 'The connection will be initiated via insecure HTTP! (add https://)')}}
					</label>
            </div>
            <NcButton
                text="Connect"
                @click="updateState()">
                <template #icon>
                    <ConnectionIcon
                        title=""
                        :size="20" />
                </template>
                Connect
            </NcButton>
        </NcSettingsSection>
    </div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { NcSettingsSection, NcPasswordField, NcTextField, NcCheckboxRadioSwitch, NcButton, NcNoteCard, NcTimezonePicker, NcLoadingIcon, NcSelect } from '@nextcloud/vue'
import LockIcon from 'vue-material-design-icons/Lock.vue'
import WebIcon from 'vue-material-design-icons/Web.vue'
import ConnectionIcon from 'vue-material-design-icons/Connection.vue'
import EarthIcon from 'vue-material-design-icons/Earth.vue'
import ContentSaveIcon from 'vue-material-design-icons/ContentSave.vue'
import AlertShieldIcon from 'vue-material-design-icons/ShieldAlert.vue'

export default ({
    components: {
        NcSettingsSection,
        NcPasswordField,
        NcTextField,
        NcCheckboxRadioSwitch,
        NcButton,
        LockIcon,
        WebIcon,
        EarthIcon,
        ConnectionIcon,
        NcNoteCard,
        NcTimezonePicker,
        NcLoadingIcon,
        ContentSaveIcon,
        AlertShieldIcon,
        NcSelect
    },

    data() {
		return {
            connected: false,
            checkingConnection: false,
			state: loadState('archive', 'admin-settings')
		}
	},

    mounted() {
        console.log('Loaded state is: ' + JSON.stringify(loadState('archive', 'admin-settings')))
        /* Check connection on page load with current settíngs */
        this.checkConnection()
	},

    computed: {
        urlIsSecure() {
            /* Matches https:// */
            let secureUrl = new RegExp(/https:\/\//)
            return (this.state.url ==='') ? true : secureUrl.test(this.state.url)
        }
    },

    methods: {
        printState() {
            console.log('Updated state is: ' + JSON.stringify(this.state))
        },

        clearUrl() {
            this.state.url = ''
        },

        /* Check connection to Archive server API */
        checkConnection() {
            this.checkingConnection = true
            const url = generateUrl('/apps/archive/connected')

			axios.get(url).then((response) => {
				showSuccess(t('archive', 'Successfully connected'))
                this.connected = true
                this.checkingConnection = false
			}).catch((error) => {
				console.debug(error)
                this.connected = false
                this.checkingConnection = false
			})
        },

        /* Update local settings in config */
		async updateState() {
			const url = generateUrl('/apps/archive/admin-settings')
			
            const req = {
                state: this.state
            }

			axios.put(url, req).then((response) => {
				showSuccess(t('archive', 'Successfully updated archive configuration'))
                /* Check connection with new settings */
                this.checkConnection()
			}).catch((error) => {
				showError(
					t('archive', 'Failed to updated archive configuration')
					+ ': ' + (error.response?.request?.responseText ?? '')
				)
				console.debug(error)
			})
		},
	},
})
</script>

<style lang="scss" scoped>
    .wrapper {
        display: flex;
        gap: 4px;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .toggle-container {
        margin: 7px 0 7px 0;
    }

    .field-label {
		display: flex;
		align-items: center;
		margin: 12px 0;
		span {
			margin-left: 8px;
		}
	}

    .external-label label {
        padding-top: 7px;
        padding-right: 14px;
        white-space: nowrap;
    }

    .warning-container {
        margin: 7px 0 7px 0;
        display: flex;
        > label {
            margin-left: 8px;
        }
        .warning-icon {
            color: var(--color-warning);
        }
    }
</style>