<?php
//
// Description
// -----------
// This function will use the bme280.py script to poll a bme280 on the i2c bus for weather data.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_i2c_pollBME280(&$ciniki, $tnid, $device) {

    $python = '/usr/bin/python';
    if( isset($ciniki['config']['ciniki.core']['python']) && $ciniki['config']['ciniki.core']['python'] != '' ) {
        $python = $ciniki['config']['ciniki.core']['python'];
    }

    if( isset($ciniki['config']['qruqsp.core']['modules_dir']) ) {
        $mod_dir = $ciniki['config']['qruqsp.core']['modules_dir'];
    } else {
        $mod_dir = $ciniki['config']['ciniki.core']['root_dir'] . '/qruqsp-mods';
    }

    $cmd = $python . ' ' . $mod_dir . '/i2c/scripts/bme280.py ' . $device['bus_number'] . ' ' . dechex($device['address']);

    $output = shell_exec($cmd);

    if( $output === false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.i2c.21', 'msg'=>'Unable to execute bme280 python script'));
    }

    $rsp = json_decode($output, true);

    if( $rsp == null || $rsp === false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.i2c.18', 'msg'=>'Unable to decode bme280 python script'));
    }

    $dt = new DateTime('now', new DateTimezone('UTC'));

    //
    // Add the current GPS coordinates to the response
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'tenantGPSCoords');
    $rc = ciniki_tenants_hooks_tenantGPSCoords($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.i2c.19', 'msg'=>'Unable to get GPS Coordinates', 'err'=>$rc['err']));
    }
    $rsp['station'] = $ciniki['config']['ciniki.core']['sync.name'];
    $rsp['latitude'] = $rc['latitude'];
    $rsp['longitude'] = $rc['longitude'];
    $rsp['altitude'] = $rc['altitude'];
    //
    // Check if pressure should be corrected for altitude
    //
    if( isset($device['flags']) && ($device['flags']&0x02) == 0x02 && $rsp['altitude'] != 0 ) {
        $rsp['millibars'] = $rsp['millibars'] * pow(1-((0.0065 * $rsp['altitude'])/($rsp['celsius'] + (0.0065 * $rsp['altitude']) + 273.15)), -5.257);
    }
    $rsp['sensor'] = 'bme280';
    $rsp['sample_date'] = $dt->format('Y-m-d H:i:s');

    return $rsp;
}
?>
