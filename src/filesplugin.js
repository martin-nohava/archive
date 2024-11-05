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

import SubmitFilesModal from './components/SubmitFilesModal.vue'

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { registerFileAction } from '@nextcloud/files'
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

import Vue from 'vue'
import './bootstrap.js'

const DEBUG = true

function initModal(files) {
	/* Global varialbe holding selected files */
	OCA.Archive.filesToSubmit = files
	const modal = OCA.Archive.SubmitFilesModalVue
	modal.setFiles([...files])
	modal.showModal()
}

/* FilesPlugin */
(function () {
	if (!OCA.Archive) {
		/**
		 * @namespace
		 */
		OCA.Archive = {
			filesToSubmit: [],
		}
	}

	/**
	 * @namespace
	 */
	OCA.Archive.FilesPlugin = {
		id: "archiveArchive",
		displayName: (files, view) => 'Archive',
		iconSvgInline: (files, view) => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" version="1.1" height="16"><path fill="#fff" d="m1 2v2h14v-2zm1 3v9h12v-9zm3.5 2h5v1h-5z"/></svg>',
		enabled: (files, view) => true,
		order: -101,
		exec: async (file, view, dir) => {
			const filesToArchive = [
				{
					id: file.fileid,
					name: file.basename,
					type: file.type,
					size: file.size,
				},
			]
			initModal(filesToArchive)
			return Promise.resolve(null);
		},
		execBatch: async (files, view, dir) => {
			const filesToArchive = files.map((f) => {
				return {
					id: f.fileid,
					name: f.basename,
					type: f.type,
					size: f.size,
				}
			})
			initModal(filesToArchive)
			return Promise.resolve(null);
		}
	}
})()

/* Send file ID to application PHP backend */
function submitFile(deleteFiles, comment) {
	/* Loop over filesToSubmit until array is empty */
	const file = OCA.Archive.filesToSubmit.shift()
	/* Pass ID of file which is being processed to modal */
	OCA.Archive.SubmitFilesModalVue.fileStarted(file.id)
	const req = {
		fileId: file.id,
		comment: comment
	}
	/* Conatct backend with ID of file to send */
	const url = generateUrl('apps/archive/submit-file')
	axios.post(url, req).then(() => {
		if (DEBUG) console.debug('[Archive] sending file to backend')
		/* Store names of submitted files (for use in notifications) */
		OCA.Archive.submittedFileNames.push(file.name)
		/* Pass ID of finished file to modal */
		OCA.Archive.SubmitFilesModalVue.fileFinished(file.id)

		/* Remove file after archivation if requested */
		if (deleteFiles === true) {
			if (DEBUG) console.debug('[Archive] remmoving file from list')
			OCA.Files.App.fileList.do_delete(file.name)
		}
		/* If all files to archive were processed */
		if (OCA.Archive.filesToSubmit.length === 0) {
			/* Emmit notification and close modal */
			submissionSuccess()
		} else {
			/* Call this function again */
			submitFile(deleteFiles, comment)
		}
	}).catch((error) => {
		console.error(error)
		OCA.Archive.SubmitFilesModalVue.failure()
		OCA.Archive.filesToSubmit = []
		OCA.Archive.submittedFileNames = []
		showError(
			t('archive', 'Failed to submit {name} to Archive', { name: file.name })
			+ ' ' + error.response?.request?.responseText
		)
	})
}

/* Close modal and cleanup */
function submissionSuccess() {
	/* Number of submitted files to archive  */
	const count = OCA.Archive.submittedFileNames.length
	/* Name of the last submitted file */
	const lastFileName = count === 0 ? t('archive', 'Nothing') : OCA.Archive.submittedFileNames[count - 1]
	/* Show notification */
	showSuccess(n('archive', 'File {fileName} was archived', '{count} files were archived', count, { fileName: lastFileName, count }))

	/* Clean arrays */
	OCA.Archive.filesToSubmit = []
	OCA.Archive.submittedFileNames = []

	/* Close modal with sucess */
	OCA.Archive.SubmitFilesModalVue.success()
}

/* Prepare mount point for modal */
const modalId = 'archiveSubmitModal'
const modalElement = document.createElement('div')
modalElement.id = modalId
document.body.append(modalElement)

/* Load modal .vue file and attach it to the mount point */
const View = Vue.extend(SubmitFilesModal)
OCA.Archive.SubmitFilesModalVue = new View().$mount(modalElement)

/* Event listeners */
OCA.Archive.SubmitFilesModalVue.$on('closed', () => {
	if (DEBUG) console.debug('[Archive] modal closed')
})
OCA.Archive.SubmitFilesModalVue.$on('validate', ({ filesToSubmit, deleteFiles, comment }) => {
	OCA.Archive.filesToSubmit = filesToSubmit

	if (DEBUG) console.debug('[Archive] deleteFiles: ' + deleteFiles)
	if (DEBUG) console.debug('[Archive] comment: ' + comment)
	OCA.Archive.submittedFileNames = []
	submitFile(deleteFiles, comment)
})

/* Register custom file plugin */
document.addEventListener('DOMContentLoaded', () => {
	if (DEBUG) console.debug('[Archive] before register files plugin')
	registerFileAction(OCA.Archive.FilesPlugin)
})