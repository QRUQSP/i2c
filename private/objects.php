<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_i2c_objects(&$ciniki) {
    //
    // Build the objects
    //
    $objects = array();

    $objects['device'] = array(
        'name' => 'i2c Device',
        'sync' => 'yes',
        'o_name' => 'device',
        'o_container' => 'devices',
        'table' => 'qruqsp_i2c_devices',
        'fields' => array(
            'bus_number' => array('name'=>'Bus'),
            'address' => array('name'=>'Address'),
            'status' => array('name'=>'Status', 'default'=>'10'),
            'device_type' => array('name'=>'Type', 'default'=>'0'),
            'name' => array('name'=>'Name', 'default'=>''),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            ),
        'history_table' => 'qruqsp_i2c_history',
        );
    //
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
