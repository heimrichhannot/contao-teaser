<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2016 Heimrich & Hannot GmbH
 *
 * @author  Rico Kaltofen <r.kaltofen@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */

$dc = &$GLOBALS['TL_DCA']['tl_content'];

$dc['config']['onload_callback'][] = array('HeimrichHannot\Teaser\Backend\Content', 'modifyPalette');

/**
 * Selector
 */
array_insert($dc['palettes']['__selector__'], 0, 'source');

/**
 * Palettes
 */
$dc['palettes']['linkteaser'] = '
								{type_legend},type,headline;
								{teaser_legend},source,teaserLinkText,teaserLinkCssClass,teaserLinkBehaviour,teaserContentTemplate;
								{text_legend},text;
								{image_legend},addImage;
								{template_legend:hide},customTpl;
								{protected_legend:hide},protected;
								{expert_legend:hide},guests,cssID,space;
								{invisible_legend:hide},invisible,start,stop';

/**
 * Subpalettes
 */
$dc['subpalettes']['source_page']     = 'jumpTo';
$dc['subpalettes']['source_file']     = 'fileSRC';
$dc['subpalettes']['source_download'] = 'fileSRC';
$dc['subpalettes']['source_article']  = 'articleId';
$dc['subpalettes']['source_external'] = 'url,target';


/**
 * Fields
 */
$arrFields = array(
    'source'                => array(
        'label'            => &$GLOBALS['TL_LANG']['tl_content']['source'],
        'default'          => 'page',
        'exclude'          => true,
        'filter'           => true,
        'inputType'        => 'radio',
        'options_callback' => array('HeimrichHannot\Teaser\Backend\Content', 'getSourceOptions'),
        'reference'        => &$GLOBALS['TL_LANG']['tl_content']['reference']['source'],
        'eval'             => array('submitOnChange' => true, 'helpwizard' => true, 'mandatory' => true),
        'sql'              => "varchar(12) NOT NULL default ''",
    ),
    'jumpTo'                => array(
        'label'      => &$GLOBALS['TL_LANG']['tl_content']['jumpTo'],
        'exclude'    => true,
        'inputType'  => 'pageTree',
        'foreignKey' => 'tl_page.title',
        'eval'       => array('mandatory' => true, 'fieldType' => 'radio'),
        'sql'        => "int(10) unsigned NOT NULL default '0'",
        'relation'   => array('type' => 'belongsTo', 'load' => 'lazy'),
    ),
    'fileSRC'               => array(
        'label'         => &$GLOBALS['TL_LANG']['tl_content']['fileSRC'],
        'exclude'       => true,
        'inputType'     => 'fileTree',
        'eval'          => array('filesOnly' => true, 'fieldType' => 'radio', 'mandatory' => true, 'tl_class' => 'clr'),
        'load_callback' => array(
            array('HeimrichHannot\Teaser\Backend\Content', 'setFileSrcFlags'),
        ),
        'sql'           => "binary(16) NULL",
    ),
    'articleId'             => array(
        'label'            => &$GLOBALS['TL_LANG']['tl_content']['articleId'],
        'exclude'          => true,
        'inputType'        => 'select',
        'options_callback' => array('HeimrichHannot\Teaser\Backend\Content', 'getArticleAlias'),
        'eval'             => array('chosen' => true, 'mandatory' => true),
        'sql'              => "int(10) unsigned NOT NULL default '0'",
    ),
    'teaserLinkText'        => array(
        'label'            => &$GLOBALS['TL_LANG']['tl_content']['teaserLinkText'],
        'exclude'          => true,
        'search'           => true,
        'inputType'        => 'select',
        'options_callback' => array('HeimrichHannot\Teaser\Backend\Content', 'getTeaserLinkText'),
        'eval'             => array('tl_class' => 'w50 clr', 'maxlength' => 64),
        'sql'              => "varchar(64) NOT NULL default ''",
    ),
    'teaserLinkCssClass'    => array(
        'label'     => &$GLOBALS['TL_LANG']['tl_content']['teaserLinkCssClass'],
        'exclude'   => true,
        'inputType' => 'text',
        'eval'      => array('tl_class' => 'w50', 'maxlength' => 64),
        'sql'       => "varchar(64) NOT NULL default ''",
    ),
    'teaserLinkBehaviour'   => array(
        'label'     => &$GLOBALS['TL_LANG']['tl_content']['teaserLinkBehaviour'],
        'exclude'   => true,
        'inputType' => 'select',
        'default'   => 'default',
        'options'   => array('default', 'linkAll', 'hideLink'),
        'reference' => &$GLOBALS['TL_LANG']['tl_content']['reference']['teaserLinkBehaviour'],
        'eval'      => array('tl_class' => 'w50 clr'),
        'sql'       => "varchar(32) NOT NULL default ''",
    ),
    'teaserContentTemplate' => array(
        'label'            => &$GLOBALS['TL_LANG']['tl_content']['teaserContentTemplate'],
        'exclude'          => true,
        'inputType'        => 'select',
        'options_callback' => array('HeimrichHannot\Teaser\Backend\Content', 'getTeaserContentTemplates'),
        'eval'             => array('tl_class' => 'w50', 'includeBlankOption' => true),
        'sql'              => "varchar(64) NOT NULL default ''",
    ),
);

$dc['fields'] = array_merge($dc['fields'], $arrFields);