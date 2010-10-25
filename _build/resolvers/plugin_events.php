<?php
/**
 * Resolver to handle plugin events
 *
 * @package geshi
 * @subpackage build
 */
$success= true;
if ($pluginid = $object->get('id')) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $events = array(
				'OnDiscussPostCustomParser',
				'OnDiscussBeforePostSave',
            );

            foreach ($events as $eventName) {
				$object->xpdo->log(xPDO::LOG_LEVEL_INFO,'Attempting to attach plugin to event: '.$eventName);
				
                $event = $object->xpdo->getObject('modEvent',array('name' => $eventName));
                if ($event) {
					
                    $pluginEvent = $object->xpdo->getObject('modPluginEvent',array(
                        'pluginid' => $pluginid,
                        'event' => $eventName,
                    ));
                    if (!$pluginEvent) {
                        $pluginEvent= $object->xpdo->newObject('modPluginEvent');
                        $pluginEvent->set('pluginid', $pluginid);
                        $pluginEvent->set('event', $eventName);
                        $pluginEvent->set('priority', 0);
                        $pluginEvent->set('propertyset', 0);
                        $success= $pluginEvent->save();
                    } else {
                        $object->xpdo->log(xPDO::LOG_LEVEL_INFO,'Plugin already connected to event '.$eventName.', skipping...');
                    }

                } else {
                    $object->xpdo->log(xPDO::LOG_LEVEL_ERROR,'Event not found: '.$eventName);
                }
                unset($event,$pluginEvent);
            }
            unset($events,$eventName);
            break;
        case xPDOTransport::ACTION_UNINSTALL:
            $success= true;
            break;
    }
}

return $success;