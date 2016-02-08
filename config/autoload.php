<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'HeimrichHannot',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Elements
	'HeimrichHannot\Teaser\ContentLinkTeaser' => 'system/modules/teaser/elements/ContentLinkTeaser.php',

	// Classes
	'HeimrichHannot\Teaser\Backend\Content'   => 'system/modules/teaser/classes/backend/Content.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'ce_linkteaser'                  => 'system/modules/teaser/templates/elements',
	'linkteaser_content_image_above' => 'system/modules/teaser/templates/linkteaser/content',
	'linkteaser_content_image_right' => 'system/modules/teaser/templates/linkteaser/content',
	'linkteaser_content_image_left'  => 'system/modules/teaser/templates/linkteaser/content',
	'linkteaser_content_image_below' => 'system/modules/teaser/templates/linkteaser/content',
	'linkteaser_link_default'        => 'system/modules/teaser/templates/linkteaser/links',
));
