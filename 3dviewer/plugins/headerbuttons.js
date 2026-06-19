OV.RegisterHeaderPlugin ({
    registerButtons : (pluginInterface) => {
        pluginInterface.createHeaderButton ('info', OV.UI.Loc ('User manual'), 'info/index.html');
        pluginInterface.createHeaderButton ('donate', OV.UI.Loc ('Support the development'), 'https://github.com/sponsors/kovacsv');
        pluginInterface.createHeaderButton ('github', OV.UI.Loc ('View on GitHub'), 'https://github.com/kovacsv/Online3DViewer');
    }
});
