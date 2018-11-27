<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_i2c_devicesProbe(&$ciniki, $tnid) {

    //
    // Setup the i2cdetect command
    //
    $cmd = '/usr/sbin/i2cdetect';
    if( isset($ciniki['config']['qruqsp.i2c']['i2cdetect_cmd']) && $ciniki['config']['qruqsp.i2c']['i2cdetect_cmd'] != '' ) {
        $cmd = $ciniki['config']['qruqsp.i2c']['i2cdetect_cmd'];
    }

    //
    // Get the list of existing devices
    //
    $strsql = "SELECT id, bus_number, address, status "
        . "FROM qruqsp_i2c_devices "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'qruqsp.i2c', array(
        array('container'=>'buses', 'fname'=>'bus_number', 'fields'=>array()),
        array('container'=>'devices', 'fname'=>'address', 'fields'=>array('id', 'bus_number', 'address', 'status')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.i2c.5', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
    }
    $buses = isset($rc['buses']) ? $rc['buses'] : array();

    //
    // Get the list of buses
    //
    $results = exec($cmd . ' -l', $buses, $rc);
    if( $rc === 0 ) {
        foreach($buses as $bus) {
            $fields = preg_split("/[\t]{1,}/", $bus);
            if( preg_match('/i2c-([0-9]+)/', $fields[0], $m) ) {
                $bus = $m[1];
                $results = exec($cmd . ' -y ' . $bus, $output, $rc);
                foreach($output as $line) {
                    $cells = preg_split("/[\s]+/", $line);
                    if( $cells[0] == '' ) {
                        // Skip the first line, which will have a blank cell 0
                        continue;
                    }
                    // Remove first cell, it's the row starter
                    array_shift($cells);
                    foreach($cells as $cell) {
                        if( $cell > 0 ) {
                            $address = hexdec($cell);
                            //
                            // Check if devices exists in database, add or update device
                            //
                            if( isset($buses[$bus]['devices'][$address]) ) {
                                $buses[$bus]['devices'][$address]['found'] = 'yes';
                                //
                                // Check status
                                //
                                if( $buses[$bus]['devices'][$address]['status'] == 50 ) {
                                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'qruqsp.i2c.device', $buses[$bus]['devices'][$address]['id'], array('status'=>10), 0x04);
                                    if( $rc['stat'] != 'ok' ) {
                                        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.i2c.6', 'msg'=>'Unable to update status of device', 'err'=>$rc['err']));
                                    }
                                }
                            } else {
                                //
                                // Add the new device
                                //
                                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
                                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'qruqsp.i2c.device', array(
                                    'bus_number' => $bus,
                                    'address' => $address,
                                    'status' => 10,
                                    ), 0x04);
                                if( $rc['stat'] != 'ok' ) {
                                    return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.i2c.7', 'msg'=>'Unable to add device', 'err'=>$rc['err']));
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    return array('stat'=>'ok');
}
?>
