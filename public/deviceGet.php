<?php
//
// Description
// ===========
// This method will return all the information about an i2c device.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the i2c device is attached to.
// device_id:          The ID of the i2c device to get the details for.
//
// Returns
// -------
//
function qruqsp_i2c_deviceGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'device_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'i2c Device'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'i2c', 'private', 'checkAccess');
    $rc = qruqsp_i2c_checkAccess($ciniki, $args['tnid'], 'qruqsp.i2c.deviceGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new i2c Device
    //
    if( $args['device_id'] == 0 ) {
        $device = array('id'=>0,
            'bus_number'=>'',
            'address'=>'',
            'status'=>'10',
            'device_type'=>'0',
            'name'=>'',
            'flags'=>'0',
        );
    }

    //
    // Get the details for an existing i2c Device
    //
    else {
        $strsql = "SELECT qruqsp_i2c_devices.id, "
            . "qruqsp_i2c_devices.bus_number, "
            . "qruqsp_i2c_devices.address, "
            . "qruqsp_i2c_devices.status, "
            . "qruqsp_i2c_devices.device_type, "
            . "qruqsp_i2c_devices.name, "
            . "qruqsp_i2c_devices.flags "
            . "FROM qruqsp_i2c_devices "
            . "WHERE qruqsp_i2c_devices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_i2c_devices.id = '" . ciniki_core_dbQuote($ciniki, $args['device_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.i2c', array(
            array('container'=>'devices', 'fname'=>'id', 
                'fields'=>array('bus_number', 'address', 'status', 'device_type', 'name', 'flags'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.i2c.11', 'msg'=>'i2c Device not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['devices'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.i2c.12', 'msg'=>'Unable to find i2c Device'));
        }
        $device = $rc['devices'][0];
    }

    return array('stat'=>'ok', 'device'=>$device);
}
?>
