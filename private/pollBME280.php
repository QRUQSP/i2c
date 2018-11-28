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
function qruqsp_i2c_pollBME280(&$ciniki, $tnid, $bus, $address) {

    $python = '/usr/bin/python';
    if( isset($ciniki['config']['ciniki.core']['python']) && $ciniki['config']['ciniki.core']['python'] != '' ) {
        $python = $ciniki['config']['ciniki.core']['python'];
    }

    if( isset($ciniki['config']['qruqsp.core']['modules_dir']) ) {
        $mod_dir = $ciniki['config']['qruqsp.core']['modules_dir'];
    } else {
        $mod_dir = $ciniki['config']['ciniki.core']['root_dir'] . '/qruqsp-mods';
    }

    $cmd = $python . ' ' . $mod_dir . '/i2c/scripts/bme280.py ' . $bus . ' ' . dechex($address);

    $output = shell_exec($cmd);

    if( $output === false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.i2c.18', 'msg'=>'Unable to execute bme280 python script'));
    }

    $rsp = json_decode($output, true);

    if( $rsp == null || $rsp === false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.i2c.18', 'msg'=>'Unable to decode bme280 python script'));
    }

    return $rsp;
}
?>
