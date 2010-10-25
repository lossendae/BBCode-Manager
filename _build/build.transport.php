<?php
/**
 * Geshi build script
 *
 * @package bbcodemanager
 * @subpackage build
 */

function getSnippetContent($filename) {
    $o = file_get_contents($filename);
    $o = str_replace('<?php','',$o);
    $o = str_replace('?>','',$o);
    $o = trim($o);
    return $o;
} 
 
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
set_time_limit(0);

/* define package */
define('PKG_NAME','BBCodeManager');
define('PKG_NAMESPACE',strtolower(PKG_NAME));
define('PKG_VERSION','0.1');
define('PKG_RELEASE','beta1');

$root = dirname(dirname(__FILE__)).'/';
$sources= array (
    'root' => $root,
    'build' => $root .'_build/',
	'data' => $root .'_build/data',
    'resolvers' => $root . '_build/resolvers/',
    'source_core' => $root . 'core/components/bbcodemanager',
    'source_assets' => $root.'assets/components/bbcodemanager',
    'docs' => $root.'core/components/bbcodemanager/docs/',
);

require_once dirname(__FILE__) . '/build.config.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx= new modX();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
echo XPDO_CLI_MODE ? '' : '<pre>';
$modx->setLogTarget('ECHO');

$modx->loadClass('transport.modPackageBuilder','',false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage(PKG_NAMESPACE,PKG_VERSION,PKG_RELEASE);
$builder->registerNamespace(PKG_NAMESPACE,false,true,'{core_path}components/'.PKG_NAMESPACE.'/');

/* create the plugin object */
$plugin= $modx->newObject('modPlugin');
$plugin->set('id',1);
$plugin->set('name', PKG_NAME);
$plugin->set('description', PKG_NAME.' '.PKG_VERSION.'-'.PKG_RELEASE.' - Plugin for MODx Revolution');
$plugin->set('plugincode', getSnippetContent($sources['source_core'] . '/elements/plugin/bbcodemanager.plugin.php'));
$plugin->set('category', 0);

/* add plugin events */
$events = include $sources['data'].'/transport.plugin.events.php';
if (is_array($events) && !empty($events)) {
    $plugin->addMany($events);
    $modx->log(xPDO::LOG_LEVEL_INFO,'Packaged in '.count($events).' Plugin Events.'); flush();
} else {
    $modx->log(xPDO::LOG_LEVEL_ERROR,'Could not find plugin events!');
}

/* load plugin properties */
/*$properties = include $sources['build'].'/properties/properties.plugin.php';
if (is_array($properties)) {
    $modx->log(xPDO::LOG_LEVEL_INFO,'Set '.count($properties).' plugin properties.'); flush();
    $plugin->setProperties($properties);
} else {
    $modx->log(xPDO::LOG_LEVEL_ERROR,'Could not set plugin properties.');
}

$properties = include_once $sources['build'].'properties/properties.plugin.php';
if (is_array($properties)) $plugin->setProperties($properties);*/

$attributes= array(
    xPDOTransport::UNIQUE_KEY => 'name',
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
        'PluginEvents' => array(
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => false,
            xPDOTransport::UNIQUE_KEY => array('pluginid','event'),
        ),
    ),
);

$vehicle = $builder->createVehicle($plugin, $attributes);
$builder->putVehicle($vehicle);

unset($plugin,$attributes);

/* create category */
// $modx->log(modX::LOG_LEVEL_INFO,'Creating category.'); flush();
// $category = $modx->newObject('modCategory');
// $category->set('id',1);
// $category->set('category',PKG_NAME. ' - Syntax highlighter');

/* Add snippets */
// $modx->log(modX::LOG_LEVEL_INFO,'Adding in snippets.'); flush();
// $snippets = include $sources['data'].'/transport.snippets.php';

// if (is_array($snippets)) {
	// $category->addMany($snippets);
	// $modx->log(modX::LOG_LEVEL_INFO,'<strong>Packaged in '.count($snippets).' snippets.</strong>'); flush();
// } else { 
	// $modx->log(modX::LOG_LEVEL_ERROR,'Adding in snippets failed.'); 
// }
// unset($snippets);

/* create category vehicle */
// $attributes = array(
    // xPDOTransport::PRESERVE_KEYS => false,
    // xPDOTransport::UPDATE_OBJECT => true,
    // xPDOTransport::UNIQUE_KEY => 'category',
    // xPDOTransport::RELATED_OBJECTS => true,
    // xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
        // 'Snippets' => array(
            // xPDOTransport::PRESERVE_KEYS => false,
            // xPDOTransport::UPDATE_OBJECT => true,
            // xPDOTransport::UNIQUE_KEY => 'name',
        // ),
    // )
// );

// $vehicle = $builder->createVehicle($category, $attributes);
// $builder->putVehicle($vehicle);

// unset($category,$attributes);

$modx->log(modX::LOG_LEVEL_INFO,'Adding in BBCodeManager Settings.'); flush();

/* load system settings */
$settings = include $sources['data'].'/transport.settings.php';
if (empty($settings)) $modx->log(modX::LOG_LEVEL_ERROR,'Could not package in settings.');

if (empty($settings)) {
	$modx->log(modX::LOG_LEVEL_ERROR,'Could not package in settings.');
} else { 
	$attributes= array(
		xPDOTransport::UNIQUE_KEY => 'key',
		xPDOTransport::PRESERVE_KEYS => true,
		xPDOTransport::UPDATE_OBJECT => false,
	);
	foreach ($settings as $setting) {
		$vehicle = $builder->createVehicle($setting,$attributes);
		$builder->putVehicle($vehicle);
	}
	$modx->log(modX::LOG_LEVEL_INFO,'<strong>Packaged in '.count($settings).' settings.</strong>'); flush();
}
unset($settings,$setting,$attributes);

$modx->log(modX::LOG_LEVEL_INFO,'Adding in Files.'); flush();

$vehicle->resolve('file',array(
    'source' => $sources['source_core'],
    'target' => "return MODX_CORE_PATH . 'components/';",
));

// $vehicle->resolve('file',array(
    // 'source' => $sources['source_assets'],
    // 'target' => "return MODX_ASSETS_PATH . 'components/';",
// ));

/* Register event */
$vehicle->resolve('php',array(
    'source' => $sources['resolvers'].'plugin_events.php',
));

$builder->putVehicle($vehicle);

/* now pack in the license file, readme and setup options */
$modx->log(modX::LOG_LEVEL_INFO,'Adding in package attributes.'); flush();
$builder->setPackageAttributes(array(
    'license' => file_get_contents($sources['docs'] . 'license.txt'),
    'readme' => file_get_contents($sources['docs'] . 'readme.txt'),
));

$builder->pack();

$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tend= $mtime;
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f s", $totalTime);

$modx->log(modX::LOG_LEVEL_INFO,"\n<br />Package Built.<br />\nExecution time: {$totalTime}\n");

exit ();