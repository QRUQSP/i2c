<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_i2c_deviceUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'device_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'i2c Device'),
//        'bus_number'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Bus'),
//        'address'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Address'),
//        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'device_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'),
        'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
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
    $rc = qruqsp_i2c_checkAccess($ciniki, $args['tnid'], 'qruqsp.i2c.deviceUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'qruqsp.i2c');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the i2c Device in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'qruqsp.i2c.device', $args['device_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.i2c');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'qruqsp.i2c');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'qruqsp', 'i2c');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'qruqsp.i2c.device', 'object_id'=>$args['device_id']));

    return array('stat'=>'ok');
}
?>
