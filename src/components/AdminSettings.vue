<template>
    <!--
    SPDX-FileCopyrightText: Martin Nohava <martin.nohava@vut.cz>
    SPDX-License-Identifier: AGPL-3.0-or-later
    -->
	<div id="archive_prefs" class="section">
		<NcSettingsSection
            title="Connect to the Archive server"
            description="Please fill in the Archive server IP address or domain name and authentication token. If Archive server is installed in the same location as the Nextcloud instance, choose the Local option."
            doc-url="https://docs.nextcloud.com/server/19/go.php?to=admin-2fa"
            :limit-width="true">
            <NcNoteCard type="warning">
                <p>Disconnected</p>
            </NcNoteCard>

            <div class="wrapper">
                <NcTextField :value.sync="ip"
                    label="Archive server IP address"
                    placeholder="IP address and port"
                    trailing-button-icon="close"
                    :show-trailing-button="ip !== ''"
                    @trailing-button-click="clearText"
                    :label-visible="true">
                    <Connection :size="16" />
                </NcTextField>
                <NcPasswordField :value.sync="token"
                    label="Authentication token"
                    placeholder="This token is generated on Archive server install"
                    :label-visible="true">
                    <Lock :size="16" />
                </NcPasswordField>
            </div>
            <div>
                <NcCheckboxRadioSwitch :checked.sync="sharingEnabled" type="switch">Local</NcCheckboxRadioSwitch>
            </div>
            <NcButton
                :disabled="disabled"
                :readonly="readonly"
                :wide="true"
                text="Connect">
                <template #icon>
                    <Connection
                        title=""
                        :size="20" />
                </template>
                Connect
            </NcButton>
	    </NcSettingsSection>
    </div>
</template>

<script>
import { NcSettingsSection, NcPasswordField, NcTextField, NcCheckboxRadioSwitch, NcButton, NcNoteCard } from '@nextcloud/vue'
import Lock from 'vue-material-design-icons/Lock.vue'
import Connection from 'vue-material-design-icons/Connection.vue'

export default ({
    components: {
        NcSettingsSection,
        NcPasswordField,
        NcTextField,
        NcCheckboxRadioSwitch,
        NcButton,
        Lock,
        Connection,
        NcNoteCard
    },

    data() {
		return {
			sharingEnabled: true,
		}
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

    .external-label {
        display: flex;
        width: 100%;
        margin-top: 1rem;
    }

    .external-label label {
        padding-top: 7px;
        padding-right: 14px;
        white-space: nowrap;
    }
</style>