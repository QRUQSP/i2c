<?php
//
// Description
// -----------
// This script is to run the i2c script once a minute from cron to query devices configured.
//

//
// Initialize Moss by including the ciniki_api.php
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');
require_once($ciniki_root . '/ciniki-mods/core/private/checkModuleFlags.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
    error_log("unable to initialize core");
    exit(1);
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];
$ciniki['session']['user']['id'] = -3;  // Setup to Ciniki Robot

//
// Load required functions
//
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

//
// Determine which tnid to use
//
$tnid = $ciniki['config']['ciniki.core']['master_tnid'];
if( isset($ciniki['config']['qruqsp.i2c']['tnid']) ) {
    $tnid = $ciniki['config']['ciniki.core']['tnid'];
}

//
// Poll the devices for data
//
ciniki_core_loadMethod($ciniki, 'qruqsp', 'i2c', 'private', 'devicesProbe');
$rc = qruqsp_i2c_devicesProbe($ciniki, $tnid);
if( $rc['stat'] != 'ok' ) {
    ciniki_cron_logMsg($ciniki, $tnid, array('code'=>'qruqsp.i2c.4', 'msg'=>'Unable to poll devices', 'err'=>$rc['err']));
}

exit(0);
?>
