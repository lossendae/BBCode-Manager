<?php
/**
 * BBCodeManager
 *
 * This file is part of BBCodeManager, a bbcode and html parser for MODx Revolution.
 *
 * BBCodeManager is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * BBCodeManager is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * BBCodeManager; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package bbcodemanager
 */
/**
 * @name BBCodeManager
 * @author Stephane Boulard
 * @package bbcodemanager
 */
$BBCodeManager = $modx->getService('bbcodemanager','BBCodeManager',$modx->getOption('bbcodemanager.core_path',null,$modx->getOption('core_path').'components/bbcodemanager/').'model/bbcodemanager/',$scriptProperties);
if (!($BBCodeManager instanceof BBCodeManager)) return 'BBCodeManager could not be loaded';	

$e = &$modx->event;

switch($e->name){
	case 'OnDiscussPostCustomParser':				
		$output = $BBCodeManager->parse($e->params['content']);
		$e->_output = $output;
	break;
	case 'OnDiscussBeforePostSave':
		$post = $BBCodeManager->limitQuote($e->params['post']);
		$e->params['post'] = $post;
	break;
	default: break;
}
return;