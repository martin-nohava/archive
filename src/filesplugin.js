OCA.Files.fileActions.registerAction({
    name: 'moveFileToTheArchive',
    displayName: t('archive', 'Move file to the archive'),
    mime: 'file',
    permissions: OC.PERMISSION_READ,
    iconClass: 'icon-category-app-bundles',
    actionHandler: (name, context) => {
        OC.dialogs.info('The file "' + name + '" with a size of ' + context.fileInfoModel.attributes.size, 'Move file to the archive')
    },
})