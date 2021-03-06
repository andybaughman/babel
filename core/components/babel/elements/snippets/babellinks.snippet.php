<?php
/**
 * Babel
 *
 * Copyright 2010 by Jakob Class <jakob.class@class-zec.de>
 *
 * This file is part of Babel.
 *
 * Babel is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Babel is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Babel; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @package babel
 */
/**
 * BabelLinks snippet to display links to translated resources
 * 
 * Based on ideas of Sylvain Aerni <enzyms@gmail.com>
 *
 * @author Jakob Class <jakob.class@class-zec.de>
 *
 * @package babel
 * 
 * @param resourceId		optional: id of resource of which links to translations should be displayed. Default: current resource
 * @param tpl				optional: Chunk to display a language link. Default: babelLink
 * @param activeCls			optional: CSS class name for the current active language. Default: active
 * @param showUnpublished	optional: flag whether to show unpublished translations. Default: 0
 * @param showUntranslated	optional: flag whether to show links to homepage of inactive language(s) if current page is not translated in that language. Default: 1 //***mod
 * @param showCurrent		optional: include current page in outputted links. Default: 1 //***mod
 * @param urlScheme			optional: flag whether to show unpublished translations. Default: full //***mod
 */
$babel = $modx->getService('babel','Babel',$modx->getOption('babel.core_path',null,$modx->getOption('core_path').'components/babel/').'model/babel/',$scriptProperties);

if (!($babel instanceof Babel)) return;

/* be sure babel TV is loaded */
if(!$babel->babelTv) return;

/* get snippet properties */
$resourceId = $modx->resource->get('id');
if(!empty($scriptProperties['resourceId'])) {
	$resourceId = intval($modx->getOption('resourceId',$scriptProperties,$resourceId));
}
$tpl = $modx->getOption('tpl',$scriptProperties,'babelLink');
$activeCls = $modx->getOption('activeCls',$scriptProperties,'active');
$showUnpublished = $modx->getOption('showUnpublished',$scriptProperties,0);
$showUntranslated = $modx->getOption('showUntranslated',$scriptProperties,0);//***mod
$showCurrent = $modx->getOption('showCurrent',$scriptProperties,1);//***mod
$urlScheme = $modx->getOption('urlScheme',$scriptProperties,'full');//***mod

if($resourceId == $modx->resource->get('id')) {
	$contextKeys = $babel->getGroupContextKeys($modx->resource->get('context_key'));
} else {
	$resource = $modx->getObject('modResource', $resourceId);
	if(!$resource) {
		return;
	}
	$contextKeys = $babel->getGroupContextKeys($resource->get('context_key'));
}

$linkedResources = $babel->getLinkedResources($resourceId);

$output = '';
foreach($contextKeys as $contextKey) {
	$continue = true;//***mod
	if(!$showCurrent && $contextKey !== $modx->resource->get('context_key')) {//***mod - Check to see if user has chosen not to show current page and if this is the current context page
		$continue = false;//***mod
	}//***mod
	if($continue) { //***mod - Option to only include links to other translated pages. I may not need to include a link to the page I am currently on.
		$context = $modx->getObject('modContext', array('key' => $contextKey));
		if(!$context) {
			$modx->log(modX::LOG_LEVEL_ERROR, 'Could not load context: '.$contextKey);
			continue;
		}
		$context->prepare();
		$cultureKey = $context->getOption('cultureKey',$modx->getOption('cultureKey'));
		$translationAvailable = false;
		if(isset($linkedResources[$contextKey])) {
			$resource = $modx->getObject('modResource',$linkedResources[$contextKey]);
			if($resource && ($showUnpublished || $resource->get('published') == 1)) {
				$translationAvailable = true;
			}
		}
		if($translationAvailable || $showUntranslated) {//***mod - Give user the option to display/not display link to homepage if translation is not available
			if($translationAvailable) {//***mod
				$url = $context->makeUrl($linkedResources[$contextKey],'',$urlScheme);//***mod - Give user more control over generated links url scheme
			} else {//***mod - Give user the option to display/not display link to alternate homepage if translation is not available
				$url = $context->getOption('site_url', $modx->getOption('site_url'));//***mod
			}//***mod
	
			$active = ($modx->resource->get('context_key') == $contextKey) ? $activeCls : '';
			$placeholders = array(
				'cultureKey' => $cultureKey,
				'url' => $url,
				'active' => $active,
				'id' => $translationAvailable? $linkedResources[$contextKey] : '');
			$output .= $babel->getChunk($tpl,$placeholders);
		}//***mod
	}//***mod
}
  
return $output;