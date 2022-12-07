
OCA.Files.fileActions.registerAction({
    name: 'archive',
    displayName: t('my-app-id', 'Archive file'),
    mime: 'file',
    permissions: OC.PERMISSION_READ,
    iconClass: 'icon-filetype-file',
    actionHandler: (name, context) => {
        console.log('---------- file action triggered', name, context)
        OC.dialogs.info('The file "' + name + '" of type ' + context.fileInfoModel.attributes.mimetype + ' will be archived.', 'Archive file')
    },
})