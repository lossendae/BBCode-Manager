<?php
/**
 * Default properties for GeSHI Snippet
 *
 * @package geshi
 * @subpackage build
 */

$properties = array(
    array(
		'name' => 'style',
        'desc' => 'The CSS file(s) to be load for the highlighted code (located in your geshi assets directory).',
        'type' => 'textfield',
        'options' => '',
        'value' => 'geshi',
	),
	array(
		'name' => 'language',
        'desc' => 'The highlighted language name.',
        'type' => 'textfield',
        'options' => '',
        'value' => 'php',
	)
	,array(
		'name' => 'enableClasses',
        'desc' => 'Load the CSS for the highlighted class. If set to false, all styling will be done inline',
        'type' => 'combo-boolean',
        'options' => '',
        'value' => true,
	),array(
		'name' => 'tabWidth',
        'desc' => 'If you’re using the <pre> header, tabs are handled automatically by your browser, and in general you can count on good results. However, if you’re using the <div>  header, you may want to specify a tab width explicitly.',
        'type' => 'textfield',
        'options' => '',
        'value' => 2,
	),array(
		'name' => 'lineNumbers',
        'desc' => 'Enable line numbers.',
        'type' => 'combo-boolean',
        'options' => '',
        'value' => false,
	),array(
		'name' => 'lineNumberStyle',
        'desc' => 'Normal line numbers means you specify a style for them, and that style gets applied to all of them. Fancy line numbers means that you can specify a different style for each nth line number.',
        'type' => 'textfield',
        'options' => '',
        'value' => 'normal',
	),
);

return $properties;