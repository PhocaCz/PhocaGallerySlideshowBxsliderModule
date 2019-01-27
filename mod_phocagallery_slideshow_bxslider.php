<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');

// Include Phoca Gallery
if (!JComponentHelper::isEnabled('com_phocagallery', true)) {
    echo '<div class="alert alert-danger">Phoca Gallery Error: Phoca Gallery component is not installed or not published on your system</div>';
    return;
}

if (!class_exists('PhocaGalleryLoader')) {
    require_once( JPATH_ADMINISTRATOR.'/components/com_phocagallery/libraries/loader.php');
}

phocagalleryimport('phocagallery.path.path');
phocagalleryimport('phocagallery.path.route');
phocagalleryimport('phocagallery.library.library');
phocagalleryimport('phocagallery.text.text');
phocagalleryimport('phocagallery.access.access');
phocagalleryimport('phocagallery.file.file');
phocagalleryimport('phocagallery.file.filethumbnail');
phocagalleryimport('phocagallery.image.image');
phocagalleryimport('phocagallery.image.imagefront');
phocagalleryimport('phocagallery.render.renderfront');
phocagalleryimport('phocagallery.render.renderadmin');
phocagalleryimport('phocagallery.render.renderdetailwindow');
phocagalleryimport('phocagallery.ordering.ordering');
phocagalleryimport('phocagallery.picasa.picasa');
phocagalleryimport('phocagallery.html.category');

$db 		= JFactory::getDBO();
$document	= JFactory::getDocument();

$module_css_style				= trim( $params->get( 'module_css_style' ) );
$catId 							= $params->get( 'category_id', 0 );
$count							= $params->get( 'count_images', 5 );
//$width 						= $params->get( 'width', 970 );
//$height						= $params->get( 'height', 230 );
$image_ordering 				= $params->get( 'image_ordering', 9 );
$url_link 						= $params->get( 'url_link', 0 );
$single_link	 				= $params->get( 'single_link', '' );
$target		 					= $params->get( 'target', '_self' );
$s['params'] 					= $params->get( 'slideshow_params', 'auto: true,pager: false,speed: 1500, controls: false, easing: \'easeInBounce\'' );
$load_bxslider_css		 		= $params->get( 'load_bxslider_css', 1 );

if ($load_bxslider_css == 1) {
	JHTML::stylesheet('modules/mod_phocagallery_slideshow_bxslider/javascript/jquery.bxslider.css' );
}
JHTML::stylesheet('modules/mod_phocagallery_slideshow_bxslider/css/style.css' );
//$document->addScript(JURI::base(true).'/components/com_phocagallery/assets/jquery/jquery-1.6.4.min.js');
JHtml::_('jquery.framework', false);

$document->addScript(JURI::base(true).'/media/mod_phocagallery_slideshow_bxslider/javascript/plugins/jquery.easing.1.3.js');
$document->addScript(JURI::base(true).'/media/mod_phocagallery_slideshow_bxslider/javascript/plugins/jquery.fitvids.js');
$document->addScript(JURI::base(true).'/media/mod_phocagallery_slideshow_bxslider/javascript/jquery.bxslider.js');

$catidSQL = '';
if ((int)$catId > 0) {
	$catidSQL = ' AND a.catid = ' . (int)$catId;
}

if ($image_ordering == 9) {
	$imageOrdering = ' ORDER BY RAND()';
} else {
	$iOA = PhocaGalleryOrdering::getOrderingString($image_ordering);
	$imageOrdering = $iOA['output'];
}

$query = ' SELECT a.id, a.title, a.description, a.filename, a.extl, a.extlink1, a.extlink2, cc.id as categoryid, cc.alias as categoryalias'
. ' FROM #__phocagallery AS a'
. ' LEFT JOIN #__phocagallery_categories AS cc ON a.catid = cc.id'
. ' WHERE a.published = 1'
. $catidSQL
//. ' WHERE cc.published = 1 AND a.published = 1 AND a.catid = ' . (int)$catId
//. ' ORDER BY RAND()'
. $imageOrdering
. ' LIMIT '.(int)$count;
$db->setQuery($query);
$images = $db->loadObjectList();

$i 	= count($images);
if ($i > 0) {

	echo '<ul class="pgbx-bxslider bxslider">'. "\n";
	foreach ($images as $k => $v) {

    echo '<li>';//. "\n";

	$urlLink 	= '';
	if ($url_link == 0) {

	} else if ($url_link == 1) {
		if (isset($v->extlink1)) {
			$v->extlink1	= explode("|", $v->extlink1, 4);
			if (isset($v->extlink1[0]) && $v->extlink1[0] != '' && isset($v->extlink1[1])) {
				$urlLink = 'http://'.$v->extlink1[0];
				if (!isset($v->extlink1[2])) {
					$target = '_self';
				} else {
					$target = $v->extlink1[2];
				}
			}
		}
	} else if ($url_link == 2) {
		if (isset($v->extlink2)) {
			$v->extlink2	= explode("|", $v->extlink2, 4);
			if (isset($v->extlink2[0]) && $v->extlink2[0] != '' && isset($v->extlink2[1])) {
				$urlLink =  'http://'.$v->extlink2[0];
				if (!isset($v->extlink2[2])) {
					$target = '_self';
				} else {
					$target = $v->extlink2[2];
				}
			}
		}
	} else if ($url_link == 3) {
		$urlLink =  PhocaGalleryRoute::getCategoryRoute($v->categoryid, $v->categoryalias);

	} else {
		if ($single_link != '') {
			$urlLink 	= 'http://'.$single_link;
			$target		= '_self';
		}
	}

	echo '<a href="'.$urlLink.'" target="'.$target.'">';

	$captionOutput  = '';
	$caption 		= '';
	if ($v->title != '') {
		$caption .= $v->title;
		if ($v->description != '') {
			$caption .= ' - '. $v->description;
		}
	} else if ($v->description != '') {
		$caption .= $v->description;
	}
	$caption = htmlspecialchars($caption);
	if ($caption != '') {
		$captionOutput = 'title="'.$caption.'"';
	}


	if (isset($v->extl) &&  $v->extl != '') {
		echo '<img src="'.PhocaGalleryText::strTrimAll($v->extl).'" alt="'.htmlspecialchars($v->title).'" '.$captionOutput.' />';
	} else {
		$thumbLink	= PhocaGalleryFileThumbnail::getThumbnailName($v->filename, 'large');
		echo '<img src="'.JURI::base(true).'/'.$thumbLink->rel.'" alt="'.htmlspecialchars($v->title).'" '.$captionOutput.'  />';
	}

	echo '</a>';//. "\n";

/*	// Label
	$label = '';
	if ($s['label'] == '1') {
		$label = $v->title;
	} else if ($s['label'] == '2') {
		$label = $v->description;
	} else if ($s['label'] == '3') {
		$label = $v->title;
		if ($v->description != '') {
			$label .= ' - '. $v->description;
		}
	}

	if ($label != '') {
		echo '<div class="label_text">';
		echo '<p>'.strip_tags($label).'</p>';
		echo '</div>'. "\n";
    }*/

	echo '</li>'. "\n";
	}
	echo '</ul>';
}


$js = 'var pgBXJQ =  jQuery.noConflict();';
	$js .= 'pgBXJQ(document).ready(function(){
  pgBXJQ(\'.pgbx-bxslider\').show().bxSlider({
	'.htmlspecialchars($s['params']).'
 });
});'. "\n";

$document->addScriptDeclaration($js);
?>
