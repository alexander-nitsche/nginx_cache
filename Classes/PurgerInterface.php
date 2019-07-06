<?php
namespace Qbus\NginxCache;

/**
 * nginx_cache – TYPO3 extension to manage the nginx cache
 * Copyright (C) 2019 Qbus Internetagentur GmbH
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
 * PurgerInterface
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
interface PurgerInterface
{
    /**
     * Perform a request to the nginx backend to clear the cache for the provided url.
     *
     * @param string $url
     * @return string
     */
    public function purge(/*string */$url)/*: string*/;
}
