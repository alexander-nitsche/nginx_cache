<?php
namespace Qbus\NginxCache\Cache\Backend;

use Qbus\NginxCache\Purger;
use Qbus\NginxCache\PurgerInterface;
use TYPO3\CMS\Core\Cache\Backend\TransientBackendInterface;
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

/**
 * NginxCacheBackend
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 2 or later
 */
class NginxCacheBackend extends \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend implements TransientBackendInterface
{
    /**
     * @var string
     */
    protected $purgerClassName = Purger::class;

    /**
     * @var PurgerInterface
     */
    protected $purger;

    /**
     * Saves data in a cache file.
     *
     * @param  string               $entryIdentifier An identifier for this specific cache entry
     * @param  string               $data            The data to be stored
     * @param  array                $tags            Tags to associate with this cache entry
     * @param  int                  $lifetime        Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
     * @return void
     * @throws Exception            if no cache frontend has been set.
     * @throws InvalidDataException if the data to be stored is not a string.
     */
    public function set($entryIdentifier, $data, array $tags = array(), $lifetime = null)
    {
        parent::set($entryIdentifier, $data, $tags, $lifetime);

        if ($lifetime === 0) {
            // unlimited is not supported by nginx
            $lifetime = 24 * 60 * 60;
        }

        /* Note: We use an explicit opt-in strategy to define requests as cachable.
         * That means this functionality relies on the "fastcgi_cache_valid 0"
         * in nginx.conf as documented in README.rst  */
        header('X-Accel-Expires: ' . $lifetime);
    }

    /**
     * Removes all cache entries matching the specified identifier.
     *
     * @param  string $entryIdentifier Specifies the cache entry to remove
     * @return bool   TRUE if (at least) an entry could be removed or FALSE if no entry was found
     */
    public function remove($entryIdentifier)
    {
        $url = parent::get($entryIdentifier);
        if ($url === false) {
            /* The key is not available. Do nothing. */
            return false;
        }

        $this->getPurger()->purge($url);

        return parent::remove($entryIdentifier);
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     */
    public function flush()
    {
        /* FIXME: this won't work for cli requests. We could try do derive the site_url from
         * existing cache entries (using findIdentifierByTag?).
         * Or introduce a configure option to set the flushAll URL. */
        if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) {
            return;
        }

        $url = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . '*';
        $this->getPurger()->purge($url);

        parent::flush();
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param  string $tag The tag the entries must have
     * @return void
     */
    public function flushByTag($tag)
    {
        $identifiers = $this->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $this->remove($identifier);
        }
    }

    /**
     * @return PurgerInterface
     */
    protected function getPurger()
    {
        if ($this->purger === null) {
            $this->purger = GeneralUtility::makeInstance($this->purgerClassName);
            if (!$this->purger instanceof PurgerInterface) {
                throw new \InvalidArgumentException(
                    'The configured nginx cache purger "' . $this->purgerClassName . '" needs to implement the PurgerInterface.',
                    1562388273
                );
            }
        }
        return $this->purger;
    }

    /**
     * Setter, called by AbstractCacheBackend when a custom purger
     * implementation has been specified in the cacheConfiguration.
     *
     * @param string $purgerClassName
     */
    public function setPurger($purgerClassName)
    {
        $this->purgerClassName = $purgerClassName;
        $this->purger = null;
    }
}
