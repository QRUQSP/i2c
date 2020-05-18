//
// This is the main app for the i2c module
//
function qruqsp_i2c_main() {
    
    this.deviceTypes = {
        '0':'Unknown',
        '10':'BME280',
    };

    //
    // The panel to list the device
    //
    this.menu = new M.panel('i2c Devices', 'qruqsp_i2c_main', 'menu', 'mc', 'medium', 'sectioned', 'qruqsp.i2c.main.menu');
    this.menu.data = {};
    this.menu.sections = {
//        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
//            'cellClasses':[''],
//            'hint':'Search device',
//            'noData':'No device found',
//            },
        'devices':{'label':'i2c Devices', 'type':'simplegrid', 'num_cols':4,
            'headerValues':['Name', 'Bus', 'Address', 'Status'],
            'noData':'No devices',
            },
    }
/*    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('qruqsp.i2c.deviceSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.qruqsp_i2c_main.menu.liveSearchShow('search',null,M.gE(M.qruqsp_i2c_main.menu.panelUID + '_' + s), rsp.devices);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.qruqsp_i2c_main.device.open(\'M.qruqsp_i2c_main.menu.open();\',\'' + d.id + '\');';
    } */
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'devices' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.bus_number;
                case 2: return d.address;
                case 3: return d.status_text;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'devices' ) {
            return 'M.qruqsp_i2c_main.device.open(\'M.qruqsp_i2c_main.menu.open();\',\'' + d.id + '\',);';
        }
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('qruqsp.i2c.deviceList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_i2c_main.menu;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.probe = function() {
        M.api.getJSONCb('qruqsp.i2c.deviceList', {'tnid':M.curTenantID, 'probe':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_i2c_main.menu;
            p.data = rsp;
            p.refresh();
            p.show();
        });
    }
    this.menu.addButton('probe', 'Probe', 'M.qruqsp_i2c_main.menu.probe();');
    this.menu.addClose('Back');
    this.menu.helpSections = function() {
        return {
            '_1':{'label':'I2C devices', 'type':'htmlcontent', 
                'html':'These are devices connected to the i2c bus on the Raspberry Pi. '
                    + 'Currently QRUQSP only supports bme280, which provides temperature, humidity and pressure is supported. '
                    + 'More devices will be added in the future.',
                },
            '_2':{'label':'Finding Devices', 'type':'htmlcontent', 
                'html':'To look for new devices, click on Probe. '
                    + 'This will return the updated list of devices found on your Raspberry Pi. '
                    + 'Click on the device to get it setup.',
                },
            };
    }

    //
    // The panel to edit i2c Device
    //
    this.device = new M.panel('i2c Device', 'qruqsp_i2c_main', 'device', 'mc', 'medium', 'sectioned', 'qruqsp.i2c.main.device');
    this.device.data = null;
    this.device.device_id = 0;
    this.device.sections = {
        'general':{'label':'', 'fields':{
//            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '50':'Detached'}},
            'device_type':{'label':'Type', 'type':'select', 'options':this.deviceTypes},
            'name':{'label':'Name', 'type':'text'},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Poll'}, '2':{'name':'Altitute Correction'}}},
//            'altitude':{'label':'Altitude (meters)', 'type':'text', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.qruqsp_i2c_main.device.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.qruqsp_i2c_main.device.device_id > 0 ? 'yes' : 'no'; },
                'fn':'M.qruqsp_i2c_main.device.remove();'},
            }},
        };
    this.device.fieldValue = function(s, i, d) { return this.data[i]; }
    this.device.fieldHistoryArgs = function(s, i) {
        return {'method':'qruqsp.i2c.deviceHistory', 'args':{'tnid':M.curTenantID, 'device_id':this.device_id, 'field':i}};
    }
    this.device.open = function(cb, did) {
        if( did != null ) { this.device_id = did; }
        M.api.getJSONCb('qruqsp.i2c.deviceGet', {'tnid':M.curTenantID, 'device_id':this.device_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.qruqsp_i2c_main.device;
            p.data = rsp.device;
            p.refresh();
            p.show(cb);
        });
    }
    this.device.save = function(cb) {
        if( cb == null ) { cb = 'M.qruqsp_i2c_main.device.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.device_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('qruqsp.i2c.deviceUpdate', {'tnid':M.curTenantID, 'device_id':this.device_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('qruqsp.i2c.deviceAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_i2c_main.device.device_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.device.remove = function() {
        M.confirm('Are you sure you want to remove device?',null,function() {
            M.api.getJSONCb('qruqsp.i2c.deviceDelete', {'tnid':M.curTenantID, 'device_id':M.qruqsp_i2c_main.device.device_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.qruqsp_i2c_main.device.close();
            });
        });
    }
    this.device.addButton('save', 'Save', 'M.qruqsp_i2c_main.device.save();');
    this.device.addClose('Cancel');

    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
        
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'qruqsp_i2c_main', 'yes');
        if( ac == null ) {
            M.alert('App Error');
            return false;
        }
        
        this.menu.open(cb);
    }
}
