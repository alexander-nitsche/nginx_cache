<?php
namespace Qbus\NginxCache;

use GuzzleHttp\Exception\RequestException;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * Purger
 *
 * @author Benjamin Franzke <bfr@qbus.de>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Purger implements PurgerInterface
{
    /**
     * @param  string $url
     * @return string
     */
    public function purge(/*string */$url)/*: string*/
    {
        $content = '';

        /* RequestFactory is available as of TYPO3 8.1 */
        if (class_exists(RequestFactory::class)) {
            try {
                $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
                $response = $requestFactory->request($url, 'PURGE');

                if ($response->getStatusCode() === 200) {
                    if ($response->getHeader('Content-Type') === 'text/plain') {
                        $content = $response->getBody()->getContents();
                    }
                }
            } catch (RequestException $e) {
                error_log("request for url '" . $url . "' failed.");
                error_log($e->getMessage());
                throw $e;
            }

        } else {
            try {
                $httpRequest = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Http\HttpRequest::class, $url);
                $httpRequest->setMethod('PURGE');

                $content = $httpRequest->send()->getBody();
            } catch (\Exception $e) {
            }
        }

        return $content;
    }
}
