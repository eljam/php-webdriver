<?php
/**
 * Copyright 2004-2014 Facebook. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package WebDriver
 *
 * @author Justin Bishop <jubishop@gmail.com>
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 * @author Fabrizio Branca <mail@fabrizio-branca.de>
 */

namespace WebDriver\Service;

use WebDriver\Exception as WebDriverException;

/**
 * WebDriver\Service\CurlService class
 *
 * @package WebDriver
 */
class CurlService implements CurlServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute($requestMethod, $url, $parameters = null, $extraOptions = array())
    {
        $customHeaders = array(
            'Content-Type: application/json;charset=UTF-8',
            'Accept: application/json;charset=UTF-8',
        );
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $matches = null;
        if (preg_match("/^(.*):(.*)@(.*?)/U", $url, $matches)) {
          $url = $matches[3];
          $auth_creds = $matches[1].":".$matches[2];
          curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
          curl_setopt($curl, CURLOPT_USERPWD, $auth_creds);
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, /* CURLOPT_CONNECTTIMEOUT_MS */ 156, 30000);
        curl_setopt($curl, /* CURLOPT_CONNECTTIMEOUT_MS */ 156, 30000);

        switch ($requestMethod) {
            case 'GET':
                break;

            case 'POST':
                if ($parameters && is_array($parameters)) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parameters));
                } else {
                    $customHeaders[] = 'Content-Length: 0';
                }

                // Suppress "Expect: 100-continue" header automatically added by cURL that
                // causes a 1 second delay if the remote server does not support Expect.
                $customHeaders[] = 'Expect:';

                curl_setopt($curl, CURLOPT_POST, true);
                break;

            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;

            case 'PUT':
                if ($parameters && is_array($parameters)) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parameters));
                } else {
                    $customHeaders[] = 'Content-Length: 0';
                }

                // Suppress "Expect: 100-continue" header automatically added by cURL that
                // causes a 1 second delay if the remote server does not support Expect.
                $customHeaders[] = 'Expect:';

                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
        }

        foreach ($extraOptions as $option => $value) {
            curl_setopt($curl, $option, $value);
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $customHeaders);

        $rawResult = trim(curl_exec($curl));
        $info = curl_getinfo($curl);

        if (CURLE_GOT_NOTHING !== curl_errno($curl) && $error = curl_error($curl)) {
            $message = sprintf(
                'Curl error thrown for http %s to %s%s',
                $requestMethod,
                $url,
                $parameters && is_array($parameters)
                ? ' with params: ' . json_encode($parameters) : ''
            );

            throw WebDriverException::factory(WebDriverException::CURL_EXEC, $message . "\n\n" . $error);
        }

        curl_close($curl);

        return array($rawResult, $info);
    }
}
