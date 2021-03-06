<?php
//
// Description
// -----------
// This method will return the list of i2c Devices for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get i2c Device for.
//
// Returns
// -------
//
function qruqsp_i2c_deviceList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'probe'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Probe'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'i2c', 'private', 'checkAccess');
    $rc = qruqsp_i2c_checkAccess($ciniki, $args['tnid'], 'qruqsp.i2c.deviceList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check if probe should be run
    //
    if( isset($args['probe']) && $args['probe'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'qruqsp', 'i2c', 'private', 'devicesProbe');
        $rc = qruqsp_i2c_devicesProbe($ciniki, $args['tnid']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'i2c', 'private', 'maps');
    $rc = qruqsp_i2c_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the list of devices
    //
    $strsql = "SELECT qruqsp_i2c_devices.id, "
        . "qruqsp_i2c_devices.bus_number, "
        . "qruqsp_i2c_devices.address, "
        . "qruqsp_i2c_devices.status, "
        . "qruqsp_i2c_devices.status AS status_text, "
        . "qruqsp_i2c_devices.device_type, "
        . "qruqsp_i2c_devices.name, "
        . "qruqsp_i2c_devices.flags "
        . "FROM qruqsp_i2c_devices "
        . "WHERE qruqsp_i2c_devices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.i2c', array(
        array('container'=>'devices', 'fname'=>'id', 
            'fields'=>array('id', 'bus_number', 'address', 'status', 'status_text', 'device_type', 'name', 'flags'),
            'maps'=>array('status_text'=>$maps['device']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $devices = isset($rc['devices']) ? $rc['devices'] : array();
    foreach($devices as $iid => $device) {
        $devices[$iid]['address'] = '0x' . dechex($device['address']);
    }

    return array('stat'=>'ok', 'devices'=>$devices);
}
?>
