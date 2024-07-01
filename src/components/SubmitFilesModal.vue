<template>
	<!--
    SPDX-FileCopyrightText: Martin Nohava <martin.nohava@vut.cz>
    SPDX-License-Identifier: AGPL-3.0-or-later

	Code inspired by Mattermost integration into Nextcloud availble from
    https://github.com/julien-nc/integration_mattermost by Julien Veyssier 2022
    -->
	<div class="archive-modal-container">
		<NcModal v-if="show"
			size="normal"
			@close="closeModal">
			<div class="archive-modal-content">
				<h2 class="modal-title">
					
					<span>
						{{ n('archive', 'Archive file', 'Archive files', files.length)}}
					</span>
				</h2>
				<span class="field-label">
					<FileIcon />
					<span>
						<strong>
							{{ t('archive', 'Files') }}
						</strong>
					</span>
				</span>
				<div class="files">
					<div v-for="f in files"
						:key="f.id"
						class="file">
						<NcLoadingIcon v-if="fileStates[f.id] === STATES.IN_PROGRESS"
							:size="20" />
						<CheckCircleIcon v-else-if="fileStates[f.id] === STATES.FINISHED"
							class="check-icon"
							:size="24" />
						<img v-else
							:src="getFilePreviewUrl(f.id, f.type)"
							class="file-image">
						<span class="file-name">
							{{ f.name }}
						</span>
						<div class="spacer" />
						<span class="file-size">
							{{ myHumanFileSize(f.size, true) }}
						</span>
						<NcButton class="remove-file-button"
							:disabled="loading"
							@click="onRemoveFile(f.id)">
							<template #icon>
								<CloseIcon :size="20" />
							</template>
						</NcButton>
					</div>
				</div>
				<div class="advanced-options">
					<span class="field-label">
						<ArchiveCogIcon />
						<span>
							<strong>
								{{ t('archive', 'Advanced options') }}
							</strong>
						</span>
					</span>
					<NcCheckboxRadioSwitch :checked.sync="deleteFiles" :disabled="loading">{{ n('archive', 'Delete file after archivation', 'Delete files after archivation', files.length) }}</NcCheckboxRadioSwitch>
					<span class="field-label">
						<CommentIcon />
						<span>
							<strong>
								{{ t('archive', 'Comment') }}
							</strong>
						</span>
					</span>
					<!-- TODO: Comment function is not implemented in backend -->
					<NcTextField :value.sync="comment"
						:label="commentPlaceholder"
						:disabled="true"
						trailing-button-icon="close"
						:show-trailing-button="comment !== ''"
						@trailing-button-click="clearComment">
					</NcTextField>
					
				</div>
				<div class="archive-footer">
					<div class="spacer" />
					<div class="warning-container" v-if="!connected">
						<AlertIcon class="warning-icon" />
						<label>
							{{t('archive', 'Could not connect to the archivation server!')}}
						</label>
					</div>
					<NcButton
						@click="closeModal">
						{{ t('archive', 'Cancel') }}
					</NcButton>
					<NcButton type="primary"
						:class="{ loading, okButton: true }"
						:disabled="!optionsValid"
						@click="submit">
						<template #icon>
							<ArchivePlusIcon />
						</template>
						{{ n('archive', 'Archive file', 'Archive files', files.length) }}
					</NcButton>
				</div>
			</div>
		</NcModal>
	</div>
</template>

<script>
import { NcCheckboxRadioSwitch, NcModal, NcLoadingIcon, NcButton, NcNoteCard, NcTextField } from '@nextcloud/vue'
import FileIcon from 'vue-material-design-icons/File.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import ArchiveCogIcon from 'vue-material-design-icons/ArchiveCog.vue'
import CommentIcon from 'vue-material-design-icons/Comment.vue'
import CheckCircleIcon from 'vue-material-design-icons/CheckCircle.vue'
import ArchivePlusIcon from 'vue-material-design-icons/ArchivePlus.vue'
import AlertIcon from 'vue-material-design-icons/Alert.vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { humanFileSize, notConnectedDialog } from '../helpers.js'

const STATES = {
	IN_PROGRESS: 1,
	FINISHED: 2,
}

export default {
	name: 'ArchiveFilesModal',
	components: {
		NcCheckboxRadioSwitch,
		NcModal,
		NcLoadingIcon,
		NcButton,
		NcNoteCard,
		NcTextField,
		ArchivePlusIcon,
		FileIcon,
		ArchiveCogIcon,
		CommentIcon,
		CheckCircleIcon,
		CloseIcon,
		AlertIcon
	},
	props: [],
	data() {
		return {
			show: false,
			loading: false,
			deleteFiles: false,
			connected: true,
			comment: '',
			files: [],
			fileStates: {},
			STATES,
			commentPlaceholder: t('archive', 'Attach comment to archived files (optional)')
		}
	},
	computed: {
		optionsValid() {
			/* Valid if at least one file is selected and loading is off and server is reachable */
			return this.files.length > 0 && !this.loading && this.connected
		},
	},
	watch: {
	},
	mounted() {
		/* Reset values of modal on mount */
		this.reset()
	},
	methods: {
		reset() {
			this.files = []
			this.fileStates = {}
			this.comment = ''
			this.deleteFiles = false
			this.connected = true
		},
		clearComment() {
			this.comment = ''
		},
		showModal() {
			this.show = true
			this.checkConnection()
		},
		closeModal() {
			this.show = false
			this.$emit('closed')
			this.reset()
		},
		setFiles(files) {
			this.files = files
		},
		/* Check connection to Archive server API */
        checkConnection() {
            const url = generateUrl('/apps/archive/connected')

			axios.get(url).then((response) => {
				this.connected = true
			}).catch((error) => {
				this.connected = false
				console.debug(error)
                notConnectedDialog()
			})
        },
		submit() {
			this.loading = true
			this.$emit('validate', {
				filesToSubmit: [...this.files],
				deleteFiles: this.deleteFiles,
				comment: this.comment
			})
		},
		success() {
			this.loading = false
			this.closeModal()
		},
		failure() {
			this.loading = false
		},
		getFilePreviewUrl(fileId, fileType) {
			if (fileType === 'dir') {
				return generateUrl('/apps/theming/img/core/filetypes/folder.svg')
			}
			return generateUrl('/apps/archive/preview?id={fileId}&x=100&y=100', { fileId })
		},
		fileStarted(id) {
			this.$set(this.fileStates, id, STATES.IN_PROGRESS)
		},
		fileFinished(id) {
			this.$set(this.fileStates, id, STATES.FINISHED)
		},
		myHumanFileSize(bytes, approx = false, si = false, dp = 1) {
			return humanFileSize(bytes, approx, si, dp)
		},
		onRemoveFile(fileId) {
			const index = this.files.findIndex((f) => f.id === fileId)
			this.files.splice(index, 1)
		},
	},
}
</script>
<style scoped lang="scss">
.archive-modal-content {
	padding: 16px;
	display: flex;
	flex-direction: column;
	overflow-y: scroll;
	> *:not(.archive-footer) {
		margin-bottom: 16px;
	}
	.field-label {
		display: flex;
		align-items: center;
		margin: 12px 0;
		span {
			margin-left: 8px;
		}
	}
	> *:not(.field-label):not(.advanced-options):not(.archive-footer):not(.warning-container),
	.advanced-options > *:not(.field-label) {
		margin-left: 10px;
	}
	.advanced-options {
		display: flex;
		flex-direction: column;
	}
	.modal-title {
		display: flex;
		justify-content: center;
		span {
			margin-left: 8px;
		}
	}
	input[type='text'] {
		width: 100%;
	}
	.files {
		display: flex;
		flex-direction: column;
		.file {
			display: flex;
			align-items: center;
			margin: 4px 0;
			height: 40px;
			> *:first-child {
				width: 32px;
			}
			img {
				height: auto;
			}
			.file-name {
				margin-left: 12px;
				text-overflow: ellipsis;
				overflow: hidden;
				white-space: nowrap;
			}
			.file-size {
				white-space: nowrap;
			}
			.check-icon {
				color: var(--color-success);
			}
			.remove-file-button {
				width: 32px !important;
				height: 32px;
				margin-left: 8px;
				min-width: 32px;
				min-height: 32px;
			}
		}
	}
	.radios {
		margin-top: 8px;
		width: 250px;
	}
	.settings-hint {
		color: var(--color-text-maxcontrast);
		margin: 16px 0 16px 0;
	}
}
.spacer {
	flex-grow: 1;
}
.archive-footer {
	display: flex;
	> * {
		margin-left: 8px;
	}
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