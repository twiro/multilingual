<?php

class multilingual
{
    static $log;

    /**
     * $languages
     *
     * An array of ISO 639-1 language-codes that represent the configured
     * languages offered by this extension.
     *
     * @var array
     */

    static $languages;

    /**
     * $language
     *
     * A single ISO 639-1 language-code that represents the detected language
     * of a frontend page request.
     *
     * @var string
     */

    static $language;

    /**
     * $region
     *
     * A single ISO 3166-1 region-code that represents the detected region
     * of a frontend page request.
     *
     * @var string
     */

    static $region;

    /**
     * $language_source
     *
     * Defines how $language has been detected by one of these values:
     * 'url', 'cookie', 'browser' or 'default'.
     *
     * 'url' represents a 'direct match', meaning that a distinct and valid
     * language-code is included in a page request. this value will never
     * trigger a redirect.
     *
     * 'cookie' and 'browser' represent an 'indirect match', meaning that a page
     * is requested without including a distinct language-code, but a valid
     * match was found in either a cookie or the browser-information.
     * these values could trigger a redirect (depending on the configuration).
     *
     * 'default' represents a 'no match', meaning that the extension couldn't
     * detect a matching language in the url, a cookie or the browser-information,
     * thus falling back to the default language of the extension.
     * this value could also trigger a redirect (depending on the configuration).
     *
     * @var string
     */

    static $language_source;

    /**
     * Fetch the language-codes from the configuration file and return them
     * as an array.
     *
     * @since version 2.0.0
     */

    public function getLanguages()
    {
        if (self::$languages = Symphony::Configuration()->get('languages', 'multilingual')) {
            self::$languages = explode(',', str_replace(' ', '', self::$languages));
        }
    }

    /**
     * Detects the language of a frontend page request based on one
     * of the following methods:
     *
     * A) URL query string (e.g "/page/?language=en")
     * B) URL path (e.g. "/en/page/" )
     * C) URL (sub)domain (e.g "domain.co.uk/page/")
     * D) Cookie ("multilingual")
     * E) Browser preferences (via "accept language" header)
     *
     * Uses the default language of the extension as fallback if
     * all of these methods fail in finding a valid match.
     *
     * @since version 2.0.0
     */

    public function detectFrontendLanguage($context)
    {
        // look for a matching language by…

        // A) URL query string (e.g "/page/?language=en")

        if (isset($_GET['language']) && in_array($_GET['language'], self::$languages)) {

            self::$language = $_GET['language'];
            self::$region = substr($_GET['region'], 0, 2);
            self::$language_source = 'url';
            self::$log = 'Distinct language-code detected by url query string (A)';

        // B) URL path (e.g "/en/page/")

        } else if (preg_match('/^\/([a-z]{2})(?:-)?([a-z]{2})?\//', $context['page'], $match) && in_array($match[1], self::$languages)) {

            self::$language = $match[1];
            self::$region = $match[2];
            self::$language_source = 'url';
            self::$log = 'Distinct language-code detected by symphony url path (B)';

        // B.2) URL path (this implementation is needed to accept htaccess-based url-routing)

        } else if (preg_match('/^\/([a-z]{2})(?:-)?([a-z]{2})?\//', $_SERVER['REQUEST_URI'], $match) && in_array($match[1], self::$languages)) {

            self::$language = $match[1];
            self::$region = $match[2];
            self::$language_source = 'url';
            self::$log = 'Distinct language-code detected by url path (B.2)';

        // C) URL (sub)domain (e.g "domain.co.uk/page/")

        } else if ($match = self::detectDomainLanguage()) {

            self::$language = $match[1];
            self::$region = $match[2];
            self::$language_source = 'url';
            self::$log = 'Distinct language-code detected by (sub)domain (C)';

        // D) Cookie ("multilingual")

        } else if (preg_match('/^([a-z]{2})(?:-)?([a-z]{2})?/', $_COOKIE['multilingual'], $match) && in_array($match[1], self::$languages)) {

            self::$language = $match[1];
            self::$region = $match[2];
            self::$language_source = 'cookie';
            self::$log = 'language-code detected by cookie (D)';

        // E) Browser preferences

        } else if ($match = self::detectBrowserLanguage()) {

            self::$language = $match[1];
            self::$region = $match[2];
            self::$language_source = 'browser';
            self::$log = 'language-code detected by browser (E)';

        // if no valid matching language was found using these approaches use the extensions default language as fallback

        } else {

            self::$language = self::$languages[0];
            self::$language_source = 'default';
            self::$log = 'No language-code detected, so falling back to default language';

        }

        // if no region was found with the method that suceeded in language-detection we need to have another look

        if(!self::$region) {
            self::detectFrontendRegion();
        }
    }

    /**
     * Detects the region of a frontend page request based on either the cookie-
     * value or the browser-configuration.
     *
     * This function is needed, when a valid language-code is found in the URL,
     * but no region-code is set there.
     *
     * @since version 2.0.0
     */

    public function detectFrontendRegion()
    {
        if (preg_match('/^([a-z]{2})-([a-z]{2})?/', $_COOKIE['multilingual'], $match)) {

            self::$region = $match[2];

        } else if ($match = self::detectBrowserLanguage()) {

            self::$region = $match[2];

        }
    }

    /**
     * Checks whether or not a valid language-code is assigned to the current domain.
     *
     * If a valid match is found in the configuration the function will return an
     * array that contains the full assigned language-region-string, the extracted
     * language-code and optionally also the extracted region-code.
     *
     * Example of domain configuration data and resulting output:
     *
     * 'domain.com=en' will return ['en','en','']
     * 'us.domain.com=en-us' will return ['en-us','en','us']
     * 'domain.de=de' will return ['de','de',''];
     * 'domain.at=de-at' will return ['de-at','de','at']
     *
     * If no valid match is found this function will return false;
     *
     * @since version 2.0.0
     */

    private function detectDomainLanguage()
    {
        // get domain configuration

        $domains = self::getDomainConfiguration();

        // get the current domain name without 'www.'

        $domain = (substr(DOMAIN, 0, 4) === "www.") ? substr(DOMAIN, 4) : DOMAIN;

        // check if there is a domain configuration for the current domain

        if (array_key_exists($domain, $domains)) {

            // transform language-region-code into array separating language and region

            $code = explode('-', $domains[$domain], 2);

            // check if the extracted language-code matches the offered languages

            if (in_array($code[0], self::$languages)) {

                // return array

                return array($domains[$domain], $code[0], $code[1] );
            }
        }
        return false;
    }

    /**
     * Fetches the domain configuration data and turns it into an associative array.
     *
     * Example of the returned data:
     *
     * $domains = [
     *      'domain.com' => 'en',
     *      'us.domain.com' => 'en-us',
     *      'domain.de=de' => 'de',
     *      'domain.at' => 'de-at',
     * ]
     *
     * @since version 2.0.0
     */

    private function getDomainConfiguration()
    {
        // build associative array of domains and corresponding language-region-codes

        $domain_settings = explode(", ", Symphony::Configuration()->get('domains', 'multilingual'));

        foreach ($domain_settings as $key=> $setting) {
            $values = explode("=", $setting, 2);
            $domains[ $values[0] ] = $values[1];
        }

        return $domains;
    }

    /**
     * Analyzes the clients "Accept Language"-header and looks for matches with the
     * systems offered languages. Matches will be sorted taking the languages "q"-
     * ratings into account, so that the first match will be the best one possible.
     *
     * If a valid match is detected the function will return an array that contains
     * the full assigned language-region-string, the extracted language-code and
     * optionally also the extracted region-code.
     *
     * See detectDomainLanguage() for example of returned data.
     *
     * @since version 2.0.0
     */

    private function detectBrowserLanguage()
    {
        if ($_SERVER['HTTP_ACCEPT_LANGUAGE']) {

            // split language-string into fragments (language-codes and q-parameters)

            preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0(\.[0-9]+)?))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_fragments);

            if (count($lang_fragments[1])) {

                // create an array out of the language-code-fragments and their qualifier-values

                $langs = array_combine($lang_fragments[1], $lang_fragments[4]);

                // insert default value '1' for languages without a q-parameter
                // remove languages with a q-parameter of '0'
                // transform language-codes to lowercase

                $langs_sorted = array();

                foreach ($langs as $lang => $q) {
                    if ($q === '') {
                        $langs_sorted[strtolower($lang)] = 1;
                    } else if ($q != '0') {
                        $langs_sorted[strtolower($lang)] = $q;
                    }
                }

                // sort array based on value of q-parameter

                arsort($langs_sorted, SORT_NUMERIC);

                // match the browser languges and the configured languages
                // build an array that includes the matches and (if available) the preferred region-codes of each language

                $matches = array();

                foreach ($langs_sorted as $lang => $q) {

                    $code = substr($lang, 0, 2);

                    // check if the browser language matches on of the configured langauages

                    if (in_array($code, self::$languages))  {

                        // check if the language already has been included with a optional region code

                        if (!$matches[$code] OR !strpos($matches[$code], '-')) {

                            // if not include it's complete string in the array (including the optional region code)

                            $matches[substr($lang, 0, 2)] = $lang;
                        }
                    }
                }
            }
        }

        // return the first match if one was found, otherwise return false

        if ($matches) {

            // prepare data

            $ret_string = array_values($matches)[0];
            $ret_fragments = explode('-', $ret_string, 2);
            $ret = array($ret_string, $ret_fragments[0], $ret_fragments[1]);

            return $ret;

        } else {

            return false;
        }
    }

    /**
     * Redirects a frontend page request without distinct and valid language-
     * code to a url with a valid and distinct language-code if the extension's
     * configuration requirements for a redirect are met.
     *
     * Redirecting to (sub)domains relies on the "Domain Configuration"-data
     * and also takes the optional region-code in account when trying to
     * find the best match to redirect to.
     *
     * Redirecting via 'url-parameter' or 'url-query' don't care about the
     * region-code (yet).
     *
     * @since version 2.0.0
     */

    public function redirect($context)
    {
        // dont't redirect if a valid and distinct language-code is found in the url

        if(self::$language_source === 'url') return false;

        // get configuration

        $config_redirect = Symphony::Configuration()->get('redirect', 'multilingual');
        $config_redirect_method = Symphony::Configuration()->get('redirect_method', 'multilingual');

        // check redirect conditions…

        $redirect = false;

        // …does configuration say "always redirect"?

        if ($config_redirect === '0') $redirect = true;

        // …if configuration says "only redirect if a matching language was found" (via cookies or browser)

        if ($config_redirect === '1' && (self::$language_source === 'cookie' || self::$language_source === 'browser')) $redirect = true;

        // perform redirect if conditions are met

        if ($redirect) {

            // redirect to (sub)domain (e.g 'xy.domain.com')

            if ($config_redirect_method === '2') {

                // get domain configuration

                $domains = self::getDomainConfiguration();

                // check if a domain configuration exists that matches both the current language- and region-code

                foreach ($domains as $domain => $code) {
                    if ($code === self::$language . '-' . self::$region) {
                        $location = 'http://' . $domain;
                    }
                }

                // check if a domain configuration exists that matches the current language-code

                foreach ($domains as $domain => $code) {
                    if ( preg_match("/^" . self::$language . "(?:-)?([a-z]{2})?/", $code) ) {
                        $location = 'http://' . $domain;
                    }
                }

            // redirect to page with url-query-string (e.g 'domain.com?language=xy')

            } else if ($config_redirect_method === '1') {

                $location = URL . $context['page'] . '?language=' . self::$language;

            // redirect to page with url-parameter (e.g 'domain.com/xy/')

            } else {

                $location =  URL . '/' . self::$language . $context['page'];

            }

            // perform redirect if a location is set

            if ($location) {
                header('Location: ' . $location); exit;
            }
        }
    }

    /**
     * If a valid and distinct language-code was detected in the url this
     * function will save the language-code in a cookie.
     *
     * If a region-code is detected it will be added to the saved string.
     *
     * @since version 2.0.0
     */

    public function setCookie()
    {
        // build cookie value

        $cookie_value = self::$region ? self::$language . '-' . self::$region : self::$language;

        // check if cookie value has changed - if so save cookie

        if($cookie_value != $_COOKIE['multilingual']) {
            setcookie(
                'multilingual',
                $cookie_value,
                time() + TWO_WEEKS,
                '/',
                '.' . Session::getDomain()
            );
        }
    }
}
