<?php
/**
 * Wiki Category Tag Cloud
 *
 * @file
 * @ingroup Extensions
 * @author Daniel Friesen (http://danf.ca/mw/)
 * @version 1.3
 *
 * Derived from: YetAnotherTagCloud http://orangedino.com
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

$wgExtensionCredits['parserhook'][] = array(
	'name' => 'Wiki Category Tag Cloud',
	'version' => '1.3',
	'author' => array( '[http://danf.ca/mw/ Daniel Friesen]', 'Jack Phoenix' ),
	'descriptionmsg' => 'wikicategorytagcloud-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:WikiCategoryTagCloud',
);

// ResourceLoader support for MediaWiki 1.17+
$wgResourceModules['ext.wikicategorytagcloud'] = array(
	'styles' => 'ext.wikicategorytagcloud.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'WikiCategoryTagCloud',
	'position' => 'top'
);

$wgAutoloadClasses['WikiCategoryTagCloud'] = __DIR__ . '/WikiCategoryTagCloud.class.php';

// i18n
$wgMessagesDirs['WikiCategoryTagCloud'] = __DIR__ . '/i18n';

// Avoid unstubbing $wgParser too early on modern (1.12+) MW versions, as per r35980
$wgHooks['ParserFirstCallInit'][] = 'WikiCategoryTagCloud::register';

// Hooked function
$wgHooks['ArticleSave'][] = 'WikiCategoryTagCloud::invalidateCache';