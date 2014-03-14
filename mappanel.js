/*
 * Copyright (c) 2008-2014 The Open Source Geospatial Foundation
 * 
 * Published under the BSD license.
 * See https://github.com/geoext/geoext2/blob/master/license.txt for the full
 * text of the license.
 */

Ext.require([
    'Ext.container.Viewport',
    'Ext.layout.container.Border',
    'GeoExt.panel.Map',
    'GeoExt.container.UrlLegend',
    'GeoExt.panel.Legend'
]);

Ext.application({
    name: 'HelloGeoExt2',
    launch: function() {

        // Ext.state.Manager.setProvider(Ext.create('Ext.state.CookieProvider', {
        //     expires: new Date(new Date().getTime()+(1000*60*60*24*7)) //7 days from now
        // }));
        var mapfile = "DEU_adm1.map";

        var map = new OpenLayers.Map({});
        
        var layer = new OpenLayers.Layer.WMS( 
            "OpenLayers WMS",
            "http://localhost/cgi-bin/mapserv?map=/var/www/2213/chapter02/DEU_adm1.map", 
            {
                layers: 'DEU_adm1'
            } 
        );
        
        map.addLayers([layer]);
        
        mappanel = Ext.create('GeoExt.panel.Map', {
            title: 'The GeoExt.panel.Map-class',
            height: 400,
            width: 600,
            region: 'center',
            map: map,
            center: '12.3046875,51.48193359375',
            zoom: 6,
            stateful: true,
            stateId: 'mappanel',
            dockedItems: [{
                xtype: 'toolbar',
                dock: 'top',
                items: [{
                    text: 'Current center of the map',
                    handler: function(){
                        var c = GeoExt.panel.Map.guess().map.getCenter();
                        Ext.Msg.alert(this.getText(), c.toString());
                    }
                }]
            }]
        });

        // give the record of the 1st layer a legendURL, which will cause
        // UrlLegend instead of WMSLegend to be used
        var layerRec0 = mappanel.layers.getAt(0);
        layerRec0.set("legendURL", "http://localhost/cgi-bin/mapserv?map=/var/www/2213/chapter02/DEU_adm1.map&version=1.1.1&service=WMS&request=GetLegendGraphic&layer=DEU_adm1&format=image/png&STYLE=default");

        legendPanel = Ext.create('GeoExt.panel.Legend', {
            defaults: {
                labelCls: 'mylabel',
                style: 'padding:5px'
            },
            bodyStyle: 'padding:5px',
            width: 350,
            autoScroll: true,
            region: 'east'
        });

        Ext.create('Ext.container.Viewport', {
            layout: 'border',
            renderTo: 'view',
            width: 800,
            height: 400,
            items: [
                mappanel,
                legendPanel
            ]
        });
    }
});