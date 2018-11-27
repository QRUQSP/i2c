<?php
//
// Description
// -----------
// This function returns the int to text mappings for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_i2c_maps(&$ciniki) {
    //
    // Build the maps object
    //
    $maps = array();
    $maps['device'] = array('status'=>array(
        '10'=>'Active',
        '50'=>'Detached',
    ));
    //
    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
