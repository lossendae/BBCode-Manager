<?php
/**
 * @package geshi
 * @subpackage build
 */
$events = array();

$events['OnDiscussPostCustomParser'] = $modx->newObject('modPluginEvent');
$events['OnDiscussPostCustomParser']->fromArray(array(
    'event' => 'OnDiscussPostCustomParser',
    'priority' => 0,
    'propertyset' => 0,
),'',true,true);

$events['OnDiscussBeforePostSave'] = $modx->newObject('modPluginEvent');
$events['OnDiscussBeforePostSave']->fromArray(array(
    'event' => 'OnDiscussBeforePostSave',
    'priority' => 0,
    'propertyset' => 0,
),'',true,true);

return $events;