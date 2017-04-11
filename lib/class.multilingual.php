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
            self::$language_source = 'url';
            self::$log = 'Distinct language-code detected by url query string (A)';

        // B) URL path (e.g "/en/page/") matching a native symphony page

        } else if (preg_match('/^\/([a-z]{2})-?([a-z]{2})?\//', $context['page'], $match) && in_array($match[1], self::$languages)) {

            self::$language = $match[1];
            self::$region = $match[2];
            self::$language_source = 'url';
            self::$log = 'Distinct language-code detected by url path (B)';

        // B.2) URL path (e.g "/en/page/") matching a native symphony page by a htaccess rewrite rule

        } else if (preg_match('/^\/([a-z]{2})-?([a-z]{2})?\//', $_SERVER['REQUEST_URI'], $match) && in_array($match[1], self::$languages)) {

            self::$language = $match[1];
            self::$region = $match[2];
            self::$language_source = 'url';
            self::$log = 'Distinct language-code detected by url path (using htaccess rewrite rule) (B)';

        // C) URL (sub)domain (e.g "domain.co.uk/page/")

        } else if ($match = self::detectDomainLanguage()) {

            self::$language = $match[1];
            self::$region = $match[2];
            self::$language_source = 'url';
            self::$log = 'Distinct language-code detected by (sub)domain (C)';

        // D) Cookie ("multilingual")

        } else if (preg_match('/^([a-z]{2})-?([a-z]{2})?/', $_COOKIE['multilingual'], $match) && in_array($match[1], self::$languages)) {

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

        // if a region is set in the URL query string its value overwrites any other detection method

        if (isset($_GET['region'])) {

            self::$region = substr($_GET['region'], 0, 2);

        // if no region was found with the method that suceeded in language-detection we need to have another look

        } elseif (!self::$region) {

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

    /**
     * This function offers three methods to manipulate the htaccess-file in order
     * to make the 'default' multilingual url-structure ('/en/page/') work in the
     * frontend without having to manually include each language-code in Symphony's
     * page hierarchy or relying on non-native routing-solutions.
     *
     * @since version 2.0.0
     */

    public function setHtaccessRewriteRules($mode, $languages = null)
    {
        // get content of htaccess-file

        $htaccess = @file_get_contents(DOCROOT.'/.htaccess');

        if ($htaccess === false) return false;

        // perform htaccess manipulation

        switch ($mode) {
            case 'create':
                $htaccess = self::createHtaccessRewriteRules($htaccess);
                break;
            case 'edit':
                $htaccess = self::editHtaccessRewriteRules($htaccess, $languages);
                break;
            case 'remove':
                $htaccess = self::removeHtaccessRewriteRules($htaccess);
                break;
        }

        return @file_put_contents(DOCROOT.'/.htaccess', $htaccess);
    }

    /**
     * Takes the content of the htaccess-file, strips away any existing multi-
     * lingual rewrite rules and inserts a set of comments as placeholder for a
     * new set of rules (that will be injected afterwards). Returns the mani-
     * pulated content.
     *
     * @since version 2.0.0
     */

    private function createHtaccessRewriteRules($htaccess)
    {
        // remove existing multilingual rules from htaccess-file

        $htaccess = self::removeHtaccessRewriteRules($htaccess);

        // insert placeholder comments

        $rule = "### MULTILINGUAL REWRITE RULES - start\n    ### no language codes set\n    ### MULTILINGUAL REWRITE RULES - end";
        $htaccess = preg_replace('/(\s?### FRONTEND REWRITE)/', " {$rule}\n\n   $1", $htaccess);

        return $htaccess;
    }

    /**
     * Takes the prepared content of the htaccess-file (including a placeholder
     * for a new set of multilingual rules) and a comma-separated set of language-
     * codes and injects new rewrite rules into the htaccess-file. Returns the
     * manipulated content.
     *
     * @since version 2.0.0
     */

    private function editHtaccessRewriteRules($htaccess, $languages)
    {
        // set a token to be replaced later on as '$n'-matches won't work in a preg_replace replacement string

        $token_symphony = md5('symphony-page');

        // define the htaccess rewrite rules for the given languages

        if (!empty($languages)) {
            $languages = str_replace(', ', '|', $languages);
            $rule = "\n    RewriteCond %{REQUEST_FILENAME} !-d";
            $rule .= "\n    RewriteCond %{REQUEST_FILENAME} !-f";
            $rule .= "\n    RewriteRule ^({$languages})-?([a-z]{2})?\/(.*\/?)$ index.php?symphony-page={$token_symphony}&%{QUERY_STRING} [L]";
        } else {
            $rule = "\n    ### no language codes set";
        }

        // insert the new rules into the htaccess-content

        $htaccess = preg_replace('/(\s+### MULTILINGUAL REWRITE RULES - start)(.*?)(\s*### MULTILINGUAL REWRITE RULES - end)/s', "$1{$rule}$3", $htaccess);

        // replace the token with the real value

        $htaccess = str_replace($token_symphony, '$3', $htaccess);

        return $htaccess;
    }

    /**
     * Takes the content of the htaccess-file and strips away any existing multi-
     * lingual rewrite rules. Returns the manipulated content.
     *
     * @since version 2.0.0
     */

    private function removeHtaccessRewriteRules($htaccess)
    {
        return preg_replace('/\s+### MULTILINGUAL REWRITE RULES - start(.*?)### MULTILINGUAL REWRITE RULES - end/s', NULL, $htaccess);
    }

    /**
     * crawl through the datasource output, look for 'entry' and 'item' nodes
     * and send their content (field data) to the processFields-function that
     * will inject multilingual data into the datasource output.
     *
     * @since version 1.0.0
     */

    public function findEntries(XMLElement $xml, $node_name = 'entry')
    {
        // check if xml has child elements

        if (($elements = $xml->getChildren()) && is_array($elements)) {

            // handle elements

            foreach ($elements as $element_index => $element) {

                // check if element is xml element

                if ($element instanceof XMLElement) {

                    // check if element node name is "entry" / "item"

                    if ($element->getName() === $node_name) {

                        // process fields

                        $element = self::processFields($element);

                    } else {

                        // find entries

                        $element = self::findEntries($element);
                    }

                    // replace element

                    $xml->replaceChildAt($element_index, $element);
                }
            }
        }

        return $xml;
    }

    /**
     * datasource output fields
     *
     * @since version 1.0.0
     */

    private function processFields(XMLElement $xml)
    {
        // check if xml has child elements

        if (($elements = $xml->getChildren()) && is_array($elements)) {

            // handle elements

            foreach ($elements as $element_index => $element) {

                // check if element is xml element

                if ($element instanceof XMLElement) {

                    // get element handle

                    $element_handle = $element->getName();

                    // check if element handle is multilingual

                    if (preg_match('/-([a-z]{2})$/', $element_handle, $match)) {

                        // check if language is supported

                        if (in_array($match[1], multilingual::$languages)) {

                           // remove language segment from element handle

                           $element_handle = preg_replace('/-' . $match[1] . '$/', '', $element_handle);
                           $element_mode = $element->getAttribute('mode');

                           // set new name and language

                           $element->setName($element_handle);
                           $element->setAttribute('lang', $match[1]);
                           $element->setAttribute('translated', 'yes');

                           // store element

                           $multilingual_elements[$element_handle . ($element_mode ? ':' . $element_mode : '')][$match[1]] = $element;

                           // remove element

                           $xml->removeChildAt($element_index);
                        }

                    // process child "item" nodes (that might contain multilingual content injected by "association ouput")

                    } else {

                        // check the current element for child "item" nodes

                        $items = $element->getChildrenByName('item');

                        // process child "item" nodes

                        if (!empty($items)) {

                           self::findEntries($element, 'item');
                        }
                    }
                }
            }

            // check for stored multilingual elements

            if (is_array($multilingual_elements)) {

                // handle multilingual elements

                foreach ($multilingual_elements as $element_handle => $element) {

                    // handle languages

                    foreach (multilingual::$languages as $language) {

                        // check if element exists for each language

                        if (!isset($element[$language]) || !(str_replace('<![CDATA[]]>', '', trim($element[$language]->getValue())) || $element[$language]->getNumberOfChildren())) {

                            // fallback to default language if missing or empty

                            if (isset($element[multilingual::$languages[0]])) {

                                $element[$language] = clone $element[multilingual::$languages[0]];

                                $element[$language]->setAttribute('lang', $language);
                                $element[$language]->setAttribute('translated', 'no');
                            }
                        }
                    }

                    // readd elements

                    $xml->appendChildArray($element);
                }
            }
        }

        return $xml;
    }

}
