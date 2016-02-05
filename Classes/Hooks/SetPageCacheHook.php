<?php
namespace Qbus\NginxCache\Hooks;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
/**
 * nginx_cache – TYPO3 extension to manage the nginx cache
 * Copyright (C) 2016 Qbus GmbH
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
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * SetPageCacheHook
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 2 or later
 */
class SetPageCacheHook
{
    /**
     * @param array             $params
     * @param FrontendInterface $frontend
     */
    public function set($params, $frontend)
    {
        /* We're only intrested in the page cache */
        if ($frontend->getIdentifier() !== 'cache_pages') {
            return;
        }

        $data = $params['variable'];
        $tags = $params['tags'];
        $lifetime = $params['lifetime'];

        $uri = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        $temp_content = (isset($data['temp_content']) && $data['temp_content']);
        $tsfe = $this->getTypoScriptFrontendController();

        $cachable = (
            $temp_content === false &&
            $tsfe->isStaticCacheble() &&
            $tsfe->doWorkspacePreview() === false &&
            strpos($uri, '?') === false &&
            in_array('nginx_cache_ignore', $tags) === false
        );

        if ($cachable) {
            $this->getCacheManager()->getCache('nginx_cache')->set(md5($uri), $uri, $tags, $lifetime);
        }
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return \TYPO3\CMS\Core\Cache\CacheManager;
     */
    protected function getCacheManager()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
    }
}
