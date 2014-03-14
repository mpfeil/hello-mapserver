/*
 * Copyright (c) 2008-2014 The Open Source Geospatial Foundation
 * 
 * Published under the BSD license.
 * See https://github.com/geoext/geoext2/blob/master/license.txt for the full
 * text of the license.
 */

Ext.require([
    'Ext.container.Viewport',
    'Ext.state.Manager',
    'Ext.state.CookieProvider',
    'Ext.window.MessageBox',
    'GeoExt.panel.Map'
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

        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: [
                mappanel
            ]
        });
    }
});