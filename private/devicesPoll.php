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
function qruqsp_i2c_devicesPoll(&$ciniki, $tnid) {

    //
    // Get the list of devices on i2c that are setup for 1 minute polling
    //
    $strsql = "SELECT id, bus_number, address, status, device_type, flags "
        . "FROM qruqsp_i2c_devices "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (flags&0x01) = 0x01 "    // Poll flag set
        . "AND status = 10 "
        . "AND device_type > 0 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'qruqsp.i2c', array(
        array('container'=>'buses', 'fname'=>'bus_number', 'fields'=>array()),
        array('container'=>'devices', 'fname'=>'address', 
            'fields'=>array('id', 'bus_number', 'address', 'status', 'device_type', 'flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.i2c.5', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
    }
    $devices = isset($rc['buses']) ? $rc['buses'] : array();

    //
    // Check each device
    //
    foreach($devices as $bus) {
        foreach($bus['devices'] as $device) {
            $rc = null;
            if( $device['device_type'] == 10 ) {
                // BME 280
                ciniki_core_loadMethod($ciniki, 'qruqsp', 'i2c', 'private', 'pollBME280');
                $rc = qruqsp_i2c_pollBME280($ciniki, $tnid, $device);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.i2c.17', 'msg'=>'Unable to poll BME280', 'err'=>$rc['err']));
                }
            }

            //
            // If there was data returned, check to see if any modules want it
            //
            if( $rc != null && $rc['stat'] == 'ok' && $rc['i2c-data-type'] != '' ) {
                $data = $rc;
                $data['object'] = 'qruqsp.i2c.device';
                $data['object_id'] = $device['id'];
                foreach($ciniki['tenant']['modules'] as $module => $m) {
                    list($pkg, $mod) = explode('.', $module);
                    $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', $data['i2c-data-type'] . 'DataReceived');
                    if( $rc['stat'] == 'ok' ) {
                        $fn = $rc['function_call'];
                        $rc = $fn($ciniki, $tnid, $data);
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                    }
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
