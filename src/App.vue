<template>
    <!--
    SPDX-FileCopyrightText: Martin Nohava <martin.nohava@vut.cz>
    SPDX-License-Identifier: AGPL-3.0-or-later
    -->
	<div id="content" class="app-archive">
		<NcAppContent>
			<div class="borders">
				<div class="header">
					<div id="left">
						<h1 class="title">
							<span>
								{{ t('archive', 'Archived files') }}
							</span>
						</h1>
						<p>{{ t('archive', 'Browse your archived files in chronological order') }}</p>
					</div>
					<div class="buttons" id="right">
						<NcButton
							id="inline–button"
							type="secondary"
							text="Reload"
							@click="loadFiles()">
							Reload
						</NcButton>
						<NcButton
							type="primary"
							text="Check integrity on all files"
							:disabled="validating_all"
							@click="validateFiles()">
							<template #icon>
								<CheckCircleIcon v-if="!validating_all" :size="20" />
								<NcLoadingIcon v-else :size="20" />
							</template>
							Check integrity on all files
						</NcButton>
					</div>
				</div>
				<div v-if="loading" class="loading">
					<NcLoadingIcon :size="64" title="Loading files..."/>
				</div>
				<NcEmptyContent
					description="After you archive any file it will show up here."
					v-else-if="files.length == 0">
					<template #icon>
						<ArchiveIcon />
					</template>
					<template #title>
						<h1 class="empty-content__title">
							Archive is empty
						</h1>
					</template>
				</NcEmptyContent>
				<div v-else>
					<ul>
						<NcListItem v-for="file in files"
							:title="file.name"
							:bold="false"
							:force-display-actions="true"
							:key="file.id"
							counterType="highlighted">
							<template #icon>
								<NcAvatar :size="44" :display-name="file.name" />
							</template>
							<template #subtitle>
								{{ t('archive', 'Archived at:') }} {{file.time_of_first_ts}} | {{ t('archive', 'Valid until:') }} {{file.expiration}}
							</template>
							<template #actions>
								<!-- <NcActionButton 
								:disabled="downloading">
									<template #icon>
										<DownloadIcon v-if="!downloading" :size="20" />
										<NcLoadingIcon v-else :size="20" />
									</template>
									{{ t('archive', 'Download package') }}
								</NcActionButton> -->
								<NcActionButton
								:disabled="validating"
								@click="validateFile(file.id)">
									<template #icon>
										<CheckCircleIcon v-if="!validating" :size="20" />
										<NcLoadingIcon v-else :size="20" />
									</template>
									{{ t('archive', 'Check integrity') }}
								</NcActionButton>
							</template>
						</NcListItem>
					</ul>
				</div>
			</div>
		</NcAppContent>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { NcAppContent, NcEmptyContent, NcListItem, NcAvatar, NcActionButton, NcButton, NcLoadingIcon } from '@nextcloud/vue'
import ArchiveIcon from 'vue-material-design-icons/Archive.vue'
import DownloadIcon from 'vue-material-design-icons/Download.vue'
import CheckCircleIcon from 'vue-material-design-icons/CheckCircle.vue'

export default {
	name: 'App',
	components: {
		NcAppContent,
		NcEmptyContent,
		NcListItem,
		NcAvatar,
		NcActionButton,
		NcButton,
		NcLoadingIcon,
		ArchiveIcon,
		DownloadIcon,
		CheckCircleIcon
	},
	data() {
		return {
			files: [],
			loading: true,
			validating: false,
			validating_all: false,
			downloading: false,
		}
	},
	computed: {
		
	},
	
	async mounted() {
		this.loadFiles()
	},

	methods: {

		async loadFiles() {
			this.loading = true
			try {
				const response = await axios.get(generateUrl('/apps/archive/list-files'))
				this.files = response.data.files
			} catch (e) {
				console.error(e)
				showError(t('archive', 'Could not fetch files!'))
				this.loading = false
			}
			this.loading = false
		},

		async validateFile(id) {
			this.validating = true
			try {
				const response = await axios.get(generateUrl('/apps/archive/validate-file/' + id))
				showSuccess(response.data.message)
			} catch (e) {
				console.error(e)
				showError(t('archive', 'Could not validate file!'))
				this.validating = false
			}
			this.validating = false
		},

		async validateFiles() {
			this.validating_all = true
			try {
				const response = await axios.get(generateUrl('/apps/archive/validate-files'))
				showSuccess(response.data.message)
			} catch (e) {
				console.error(e)
				showError(t('archive', 'Could not validate files!'))
				this.validating_all = false
			}
			this.validating_all = false
		},

	},
}
</script>
<style scoped>

.title {
	font-weight: bold;
	font-size: 25px;
	line-height: 30px;
}

.header {
	margin-bottom: 20px;
	display: flex;
	justify-content: space-between;
}

.buttons {
	display: flex;
}

.loading {
	display: flex;
	justify-content: space-around;
}

#left {
	align-self: flex-start;
}

#right {
	align-self: flex-end;
}

#inline–button {
	margin-right: 10px;
}

.borders {
	margin: 25px 50px 25px 50px;
}
	
</style>
