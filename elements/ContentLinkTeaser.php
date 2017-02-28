<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2016 Heimrich & Hannot GmbH
 *
 * @package ${CARET}
 * @author  Rico Kaltofen <r.kaltofen@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */

namespace HeimrichHannot\Teaser;


class ContentLinkTeaser extends \ContentText
{

    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'ce_linkteaser';

    protected $showMore = false;

    protected $strHref;

    protected $strTitle;

    protected $strLink;

    protected $blnActive;

    protected $blnTrail;

    protected $label;

    protected $linkTemplate = 'linkteaser_link_default';

    protected $arrLinkAttributes = array();

    const LINK_CSS_CLASS = 'more';

    protected function compile()
    {
        parent::compile();

        $this->generateLink();
    }

    /**
     * Generate the teaser Link
     */
    protected function generateLink()
    {
        global $objPage;

        $this->label = $GLOBALS['TL_LANG']['MSC']['linkteaser']['teaserlinktext'][$this->teaserLinkText];
        $this->setLink(is_array($this->label) ? $this->label[0] : $this->label);

        switch ($this->source)
        {
            case 'page':
                $this->target   = false; // overwrite target
                $this->showMore = $this->handlePage();
                break;
            case 'file':
                $this->target   = true; // overwrite target, alway open in new window
                $this->showMore = $this->handleFile();
                break;
            case 'download':
                $this->target   = false; // overwrite target
                $this->showMore = $this->handleDownload();
                break;
            case 'article':
                $this->target   = false; // overwrite target
                $this->showMore = $this->handleArticle();
                break;
            case 'external':
                $this->showMore = $this->handleExternal();
                break;
            default:
                $this->showMore = false;
        }

        // HOOK: extend teaser link by callback functions
        if (isset($GLOBALS['TL_HOOKS']['generateTeaserLink']) && is_array($GLOBALS['TL_HOOKS']['generateTeaserLink']))
        {
            foreach ($GLOBALS['TL_HOOKS']['generateTeaserLink'] as $callback)
            {
                $showMore = \System::importStatic($callback[0])->{$callback[1]}($this, $this->showMore);
            }

            $this->showMore = $showMore;
        }

        if (!$this->showMore)
        {
            return false;
        }

        switch ($this->teaserLinkBehaviour)
        {
            case 'linkAll':
                $this->Template->linkAll  = true;
                $this->Template->showMore = true;
                break;
            case 'hideLink':
                $this->Template->linkAll  = true;
                $this->Template->showMore = false;
                break;
            default:
                $this->Template->linkAll  = false;
                $this->Template->showMore = true;
        }

        $this->Template->href      = $this->getHref();
        $this->Template->linkClass = static::LINK_CSS_CLASS . ($this->teaserLinkCssClass ? ' ' . $this->teaserLinkCssClass : '');

        if ($this->target)
        {
            $this->Template->target = (($objPage->outputFormat == 'xhtml') ? ' onclick="return !window.open(this.href)"' : ' target="_blank"');
        }

        $this->Template->linkTitle      = $this->getTitle();
        $this->Template->link           = $this->getLink();
        $this->Template->linkAttributes = !empty($this->getLinkAttributes()) ? ' ' . $this->getLinkAttributes(true) : '';
        $this->Template->linkTemplate   = $this->getLinkTemplate();
        $this->Template->content        = $this->generateContent();

        $this->addContainerClass($this->addImage ? 'has-image' : 'no-image');
    }

    /**
     * Generate the teaser content
     *
     * @return string The parsed teaser content
     */
    protected function generateContent()
    {
        switch ($this->floating)
        {
            case 'left':
                $strTemplate = 'linkteaser_content_image_left';
                $this->addContainerClass('float_left');
                break;
            case 'right':
                $strTemplate = 'linkteaser_content_image_right';
                $this->addContainerClass('float_right');
                break;
            case 'below':
                $strTemplate = 'linkteaser_content_image_below';
                $this->addContainerClass('float_below');
                break;
            default:
                $strTemplate = 'linkteaser_content_image_above';
                $this->addContainerClass('float_above');
        }

        // overwrite content template
        if ($this->teaserContentTemplate != '')
        {
            $strTemplate = $this->teaserContentTemplate;
        }

        $objT = new \FrontendTemplate($strTemplate);
        $objT->setData($this->Template->getData());
        // background images dont have width/height in backend view
        $objT->background = (TL_MODE == 'BE' ? false : $objT->background);

        if($this->isActive())
        {
            $this->addContainerClass('active');
        }
        else if($this->isTrail())
        {
            $this->addContainerClass('trail');
        }

        return $objT->parse();
    }

    /**
     * Handle page links
     *
     * @return bool return true, or false if the page does not exist
     */
    protected function handlePage()
    {
        global $objPage;

        $objTarget = \PageModel::findPublishedById($this->jumpTo);

        if ($objTarget === null)
        {
            return false;
        }

        $objTarget = $objTarget->loadDetails();

        if ($objTarget->target || ($objTarget->domain != '' && $objTarget->domain != \Environment::get('host')))
        {
            $this->target = true;
        }

        $this->setHref(\Controller::generateFrontendUrl($objTarget->row(), null, null, $this->target));

        // remove alias from root pages
        if ($objTarget->type == 'root')
        {
            $this->setHref(str_replace($objTarget->alias, '', $this->getHref()));
        }

        $this->setTitle(sprintf($GLOBALS['TL_LANG']['MSC']['linkteaser']['pageTitle'], $objTarget->title));
        $this->setLink(sprintf($this->getLink(), $objTarget->title));


        if(TL_MODE == 'FE' && $objPage !== null)
        {
            if ($objPage->id == $objTarget->id)
            {
                $this->setActive(true);
            }
            else if(is_array($objPage->trail) && in_array($objTarget->id, $objPage->trail))
            {
                $this->setTrail(true);
            }
        }

        return true;
    }

    /**
     * Handle files
     *
     * @return bool return true, or false if the file does not exist
     */
    protected function handleFile()
    {
        $objFile = \HeimrichHannot\Haste\Util\Files::getFileFromUuid($this->fileSRC);

        if ($objFile === null)
        {
            return false;
        }

        $arrMeta = $this->getMetaFromFile($objFile);

        $this->setHref($objFile->path);
        $this->setTitle(sprintf($GLOBALS['TL_LANG']['MSC']['linkteaser']['fileTitle'], $arrMeta['title']));
        $this->setLink(sprintf($this->getLink(), $arrMeta['title']));

        return true;
    }

    /**
     * Handle downloads
     *
     * @return bool return true, or false if the file does not exist
     */
    protected function handleDownload()
    {
        $objFile = \HeimrichHannot\Haste\Util\Files::getFileFromUuid($this->fileSRC);

        if ($objFile === null)
        {
            return false;
        }

        $allowedDownload = trimsplit(',', strtolower(\Config::get('allowedDownload')));

        // Return if the file type is not allowed
        if (!in_array($objFile->extension, $allowedDownload) || preg_match('/^meta(_[a-z]{2})?\.txt$/', $objFile->basename))
        {
            return false;
        }

        $arrMeta = $this->getMetaFromFile($objFile);

        $file = \Input::get('file', true);

        // Send the file to the browser and do not send a 404 header (see #4632)
        if ($file != '' && $file == $objFile->path)
        {
            \Controller::sendFileToBrowser($file);
        }

        $this->setHref(\Environment::get('request'));

        // Remove an existing file parameter (see #5683)
        if (preg_match('/(&(amp;)?|\?)file=/', $this->getHref()))
        {
            $this->setHref(preg_replace('/(&(amp;)?|\?)file=[^&]+/', '', $this->getHref()));
        }

        $this->setHref(
            $this->getHref() . ((\Config::get('disableAlias') || strpos($this->getHref(), '?') !== false) ? '&amp;' : '?') . 'file=' . \System::urlEncode(
                $objFile->path
            )
        );
        $this->setTitle(sprintf($GLOBALS['TL_LANG']['MSC']['linkteaser']['downloadTitle'], $arrMeta['title']));
        $this->setLink(sprintf($this->getLink(), $arrMeta['title']));

        return true;
    }

    /**
     * Handle articles
     *
     * @return bool return true, or false if the articles does not exist
     */
    protected function handleArticle()
    {
        if (($objArticle = \ArticleModel::findPublishedById($this->articleId, array('eager' => true))) === null)
        {
            return false;
        }

        if (($objTarget = \PageModel::findPublishedById($objArticle->pid)) === null)
        {
            return false;
        }

        $objTarget = $objTarget->loadDetails();

        if ($objTarget->domain != '' && $objTarget->domain != \Environment::get('host'))
        {
            $this->target = true;
        }

        $strParams = '/articles/' . ((!\Config::get('disableAlias') && $objArticle->alias != '') ? $objArticle->alias : $objArticle->id);

        $this->setHref(ampersand(\Controller::generateFrontendUrl($objTarget->row(), $strParams, null, $this->target)));
        $this->setTitle(sprintf($GLOBALS['TL_LANG']['MSC']['linkteaser']['articleTitle'], $objArticle->title));
        $this->setLink(sprintf($this->getLink(), $objArticle->title));

        return true;
    }

    /**
     * Handle external urls
     *
     * @return bool return true, or false if the url does not exist
     */
    protected function handleExternal()
    {
        if ($this->url == '')
        {
            return false;
        }

        if (substr($this->url, 0, 7) == 'mailto:')
        {
            $this->setHref(\StringUtil::encodeEmail($this->url));
            $this->setTitle(sprintf($GLOBALS['TL_LANG']['MSC']['linkteaser']['externalMailTitle'], $this->getHref()));
            $this->setLink(sprintf($this->getLink(), $this->getHref()));
        }
        else
        {
            $this->setHref(ampersand($this->url));
            $strLinkTitle = $this->getLinkTitle($this->getHref());
            $this->setTitle(sprintf($GLOBALS['TL_LANG']['MSC']['linkteaser']['externalLinkTitle'], $strLinkTitle));
            $this->setLink(sprintf($this->getLink(), $strLinkTitle));
        }

        return true;
    }

    /**
     * Generate the meta information for a given file
     *
     * @param $objFile
     *
     * @return array The meta information with i18n support
     */
    protected function getMetaFromFile(\File $objFile)
    {
        global $objPage;

        $objModel = $objFile->getModel();

        $arrMeta = $this->getMetaData($objModel->meta, $objPage->language);

        if (empty($arrMeta) && $objPage->rootFallbackLanguage !== null)
        {
            $arrMeta = $this->getMetaData($objModel->meta, $objPage->rootFallbackLanguage);
        }

        // Use the file name as title if none is given
        if ($arrMeta['title'] == '')
        {
            $arrMeta['title'] = specialchars($objFile->basename);
        }

        return $arrMeta;
    }

    /**
     * Convert {{*_url::*}} inserttags to its entity title
     *
     * @param $strHref
     *
     * @return string The link title of the element (page, article, news, event, faq)
     */
    protected function getLinkTitle($strHref)
    {
        // Replace inserttag links with title
        if (strpos($strHref, '{{') === false || strpos($strHref, '}}') === false)
        {
            return $strHref;
        }

        $arrTag = trimsplit('::', str_replace(array('{{', '}}'), '', $strHref));

        if (empty($arrTag) || $arrTag[0] == '' || $arrTag[1] == '')
        {
            return $strHref;
        }

        switch ($arrTag[0])
        {
            case 'link_url':
                return sprintf('{{link_title::%d}}', $arrTag[1]);
            case 'article_url':
                return sprintf('{{article_title::%d}}', $arrTag[1]);
            case 'news_url':
                return sprintf('{{news_title::%d}}', $arrTag[1]);
            case 'event_url':
                return sprintf('{{event_title::%d}}', $arrTag[1]);
            case 'faq_url':
                return sprintf('{{faq_title::%d}}', $arrTag[1]);
        }
    }

    protected function addContainerClass($strClass)
    {
        $this->arrData['cssID'][1] .= ' ' . $strClass;
    }

    public function setHref($varValue)
    {
        $this->strHref = $varValue;
    }

    public function getHref()
    {
        return $this->strHref;
    }

    public function setTitle($varValue)
    {
        $this->strTitle = $varValue;
    }

    public function getTitle()
    {
        return $this->strTitle;
    }

    public function setLink($varValue)
    {
        $this->strLink = $varValue;
    }

    public function getLink()
    {
        return $this->strLink;
    }

    public function setLinkTemplate($varValue)
    {
        $this->linkTemplate = $varValue;
    }

    public function getLinkTemplate()
    {
        return $this->linkTemplate;
    }

    /**
     * @return mixed
     */
    public function isActive()
    {
        return $this->blnActive;
    }

    /**
     * @param mixed $blnActive
     */
    public function setActive($blnActive)
    {
        $this->blnActive = $blnActive;
    }

    /**
     * @return mixed
     */
    public function isTrail()
    {
        return $this->blnTrail;
    }

    /**
     * @param mixed $blnTrail
     */
    public function setTrail($blnTrail)
    {
        $this->blnTrail = $blnTrail;
    }



    public function setLinkAttributes($arrData, $delimiter = " ")
    {
        // set from string
        if (!is_array($arrData))
        {
            $arrData = trimsplit($delimiter, $arrData);

            if (is_array($arrData))
            {
                foreach (array_keys($this->arrLinkAttributes) as $strKey)
                {
                    $this->arrLinkAttributes[$strKey] = $arrData[$strKey];
                }
            }

            return;
        }

        $this->arrLinkAttributes = $arrData;
    }

    public function getLinkAttributes($blnReturnString = false)
    {
        if (!$blnReturnString)
        {
            return $this->arrLinkAttributes;
        }

        $strAttributes = '';

        foreach (array_keys($this->arrLinkAttributes) as $strKey)
        {
            $strAttributes .= sprintf('%s="%s"', $strKey, $this->arrLinkAttributes[$strKey]);
        }

        return $strAttributes;
    }

    public function addLinkAttribute($key, $value)
    {
        $this->arrLinkAttributes[$key] = $value;
    }

    public function removeLinkAttribute($key)
    {
        unset($this->arrLinkAttributes);
    }
}