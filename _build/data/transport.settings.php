<?php
/**
 * Loads system settings
 *
 * @package bbcodemanager
 * @subpackage build
 */
$settings = array();

$settings['bbcodesuite.depth_quote_limit']= $modx->newObject('modSystemSetting');
$settings['bbcodesuite.depth_quote_limit']->fromArray(array(
    'key' => 'bbcodesuite.depth_quote_limit',
    'value' => 2,
    'xtype' => 'textfield',
    'namespace' => 'bbcodesuite',
    'area' => 'BBCode',
),'',true,true);

return $settings;