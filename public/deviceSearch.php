<?php
//
// Description
// -----------
// This method searchs for a i2c Devices for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get i2c Device for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function qruqsp_i2c_deviceSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'i2c', 'private', 'checkAccess');
    $rc = qruqsp_i2c_checkAccess($ciniki, $args['tnid'], 'qruqsp.i2c.deviceSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of devices
    //
    $strsql = "SELECT qruqsp_i2c_devices.id, "
        . "qruqsp_i2c_devices.bus_number, "
        . "qruqsp_i2c_devices.address, "
        . "qruqsp_i2c_devices.status, "
        . "qruqsp_i2c_devices.device_type, "
        . "qruqsp_i2c_devices.name, "
        . "qruqsp_i2c_devices.flags "
        . "FROM qruqsp_i2c_devices "
        . "WHERE qruqsp_i2c_devices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.i2c', array(
        array('container'=>'devices', 'fname'=>'id', 
            'fields'=>array('id', 'bus_number', 'address', 'status', 'device_type', 'name', 'flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['devices']) ) {
        $devices = $rc['devices'];
    } else {
        $devices = array();
    }

    return array('stat'=>'ok', 'devices'=>$devices);
}
?>
