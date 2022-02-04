<?php

if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

require_once EXTENSIONS . '/multilingual/lib/class.multilingual.php';

class Extension_Multilingual extends Extension
{

    private static $resolved;


    /**
     * GET SUBSCRIBED DELEGATES
     *
     * https://www.getsymphony.com/learn/api/2.3.3/toolkit/extension/#getSubscribedDelegates
     *
     * @since version 1.0.0
     */

    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page'     => '/frontend/',
                'delegate' => 'FrontendPrePageResolve',
                'callback' => 'frontendPrePageResolve'
            ),
            array(
                'page'     => '/frontend/',
                'delegate' => 'FrontendParamsResolve',
                'callback' => 'frontendParamsResolve'
            ),
            array(
                'page'     => '/frontend/',
                'delegate' => 'DataSourcePreExecute',
                'callback' => 'dataSourcePreExecute'
            ),
            array(
                'page'     => '/frontend/',
                'delegate' => 'DataSourceEntriesBuilt',
                'callback' => 'dataSourceEntriesBuilt'
            ),
            array(
                'page'     => '/frontend/',
                'delegate' => 'DataSourcePostExecute',
                'callback' => 'dataSourcePostExecute'
            )
        );
    }


    /**
     * INSTALL
     *
     * install the extension
     *
     * https://www.getsymphony.com/learn/api/2.3.3/toolkit/extension/#install
     *
     * @since version 2.0.0
     */

    public function install()
    {
        return true;
    }


    /**
     * ENABLE
     *
     * enable the extension
     *
     * https://www.getsymphony.com/learn/api/2.3.3/toolkit/extension/#enable
     *
     * @since version 2.0.0
     */

    public function enable()
    {
        // get values from configuration (of a possible previous install)

        $languages = Symphony::Configuration()->get('languages', 'multilingual');
        $regions = Symphony::Configuration()->get('regions', 'multilingual');
        $htaccess = Symphony::Configuration()->get('htaccess', 'multilingual');

        // check if languages are set and htaccess rewrite rules are activated

        if ($languages && $htaccess === 'yes') {

            // create htaccess rewrite rules for the configured set of languages/regions

            $htaccess_create = multilingual::setHtaccessRewriteRules('create');
            $htaccess_edit = multilingual::setHtaccessRewriteRules('edit', $languages, $regions);

        }
        return true;
    }


    /**
     * DISABLE
     *
     * disable the extension
     *
     * https://www.getsymphony.com/learn/api/2.3.3/toolkit/extension/#disable
     *
     * @since version 2.0.0
     */

    public function disable()
    {
        // remove multilingual htaccess rewrite rules

        multilingual::setHtaccessRewriteRules('remove');
    }


    /**
     * UPDATE
     *
     * update the extension
     *
     * https://www.getsymphony.com/learn/api/2.3.3/toolkit/extension/#update
     *
     * @since version 2.0.0
     */

    public function update($previousVersion = false)
    {
        // Add new default configuration settings when updating from version 1.X

        if (version_compare($previousVersion, '2.0.0', '<')) {

            // get exisiting languages/domain configuration (including some values introduced in the 2.x beta- and -rc-releases

            $languages = Symphony::Configuration()->get('languages', 'multilingual');
            $regions = Symphony::Configuration()->get('regions', 'multilingual') ? Symphony::Configuration()->get('regions', 'multilingual') : null;
            $htaccess = Symphony::Configuration()->get('htaccess', 'multilingual') ? Symphony::Configuration()->get('htaccess', 'multilingual') : 'no';
            $domains = Symphony::Configuration()->get('domains', 'multilingual') ? Symphony::Configuration()->get('domains', 'multilingual') : null;

            // add new configuration settings with their default values

            Symphony::Configuration()->setArray(
                array(
                    'multilingual' => array(
                        'languages' => $languages,
                        'languages_restricted' => null,
                        'regions' => $regions,
                        'regions_restricted' => null,
                        'htaccess' => $htaccess,
                        'domains' => $domains,
                        'redirect_mode' => '0',
                        'redirect_region' => '0',
                        'redirect_method' => '0',
                        'debug' => 'no',
                        'cookie_disable' => 'no',
                    )
                ), true
            );
            Symphony::Configuration()->write();
        }

        return true;
    }


    /**
     * UNINSTALL
     *
     * uninstall the extension
     *
     * https://www.getsymphony.com/learn/api/2.3.3/toolkit/extension/#uninstall
     *
     * @since version 1.0.0
     */

    public function uninstall()
    {
        // remove multilingual from configuration

        Symphony::Configuration()->remove('multilingual');
        Symphony::Configuration()->write();

        // remove multilingual htaccess rewrite rules

        multilingual::setHtaccessRewriteRules('remove');
    }


    /**
    * FETCH NAVIGATION
    *
    * When the Symphony navigation is being generated, this method will be called
    * to allow extensions to inject any custom backend pages into the navigation.
    *
    * http://www.getsymphony.com/learn/api/2.3.3/toolkit/extension/#fetchNavigation
    *
    * @since version 2.0.0
    */

    public function fetchNavigation()
    {
        return array(
            array (
                'location' => __('System'),
                'name' => __(multilingual::EXT_NAME),
                'link' => 'configuration',
                'limit' => 'developer',
            )
        );
    }


    /**
     * FRONTEND PRE PAGE RESOLVE
     *
     * Before page resolve. Allows manipulation of page without redirection.
     *
     * https://www.getsymphony.com/learn/api/2.3.3/delegates/#FrontendPrePageResolve
     *
     * @since version 1.0.0
     */

    public function frontendPrePageResolve($context)
    {
        if (!self::$resolved) {
            multilingual::$debug = (Symphony::Configuration()->get('debug', 'multilingual') === 'yes') ? true : false;
            multilingual::$cookie_disable = (Symphony::Configuration()->get('cookie_disable', 'multilingual') === 'yes') ? true : false;
            multilingual::getLanguages();
            multilingual::getRegions();
            multilingual::frontendDetection($context);
            multilingual::setCookie();
            self::$resolved = true;
        }
    }


    /**
     * FRONTEND PARAMS RESOLVE
     *
     * add parameters to xml output
     *
     * https://www.getsymphony.com/learn/api/2.3.3/delegates/#FrontendParamsResolve
     *
     * @since version 1.0.0
     */

    public function frontendParamsResolve($context)
    {
        // add language parameter (if at least one language is defined)

        if (multilingual::$languages) $context['params']['language'] = multilingual::$language;

        // add region parameter (if at least one region is defined)
        // (only set a value if a valid and distinct region-code was found in the url)

        if (multilingual::$regions) {
            if (multilingual::$region && multilingual::$region_source === 'url') {
                $context['params']['region'] = multilingual::$region;
            } else {
                $context['params']['region'] = '';
            }
        }

        // add additional debugging parameters

        if (multilingual::$debug) {
            if (multilingual::$languages) $context['params']['multilingual-language-source'] = multilingual::$language_log;
            if (multilingual::$regions) $context['params']['multilingual-region-source'] = multilingual::$region_log;
            $context['params']['multilingual-browser-cookie'] = $_COOKIE['multilingual'];
            $context['params']['multilingual-browser-header'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }
    }


    /**
     * DATA SOURCE PRE EXECUTE
     *
     * perform the datasource filtering
     *
     * https://www.getsymphony.com/learn/api/2.3.3/delegates/#DataSourcePreExecute
     *
     * @since version 1.0.0
     */

    public function dataSourcePreExecute($context)
    {
        // clear preexisting output

        $context['xml'] = null;

        // check if language preconditions are met

        if (multilingual::$languages && multilingual::$language !== multilingual::$languages[0]) {

            $filters = $context['datasource']->dsParamFILTERS;
            $section = $context['datasource']->getSource();

            // check if datasource has filters

            if (is_array($filters)) {

                // swap filters to current language fields

                foreach ($filters as $field_id => $filter) {

                    $field_handle = FieldManager::fetchHandleFromID($field_id);

                    // check if field handle is multilingual

                    if (preg_match('/-' . multilingual::$languages[0] . '$/', $field_handle)) {

                        // get current language field handle

                        $field2_handle = preg_replace('/-' . multilingual::$languages[0] . '$/', '-' . multilingual::$language, $field_handle);

                        // check if current language field exists

                        if ($field2_id = FieldManager::fetchFieldIDFromElementName($field2_handle, $section)) {

                            // remove default field from filters

                            unset($filters[$field_id]);

                            // add current language field to filters

                            $filters[$field2_id] = $filter;
                        }
                    }
                }

                // backup default filters

                $context['datasource']->dsDefaultFILTERS = $context['datasource']->dsParamFILTERS;

                // save current language filters

                $context['datasource']->dsParamFILTERS = $filters;
            }
        }
    }


    /**
     * DATA SOURCE ENTRIES BUILT
     *
     * datasource filtering fallback
     *
     * https://www.getsymphony.com/learn/api/2.3.3/delegates/#DataSourceEntriesBuilt
     *
     * @since version 1.0.0
     */

    public function dataSourceEntriesBuilt($context)
    {
        // check if language preconditions are met

        if (multilingual::$languages && multilingual::$language !== multilingual::$languages[0]) {

            $filters = $context['datasource']->dsDefaultFILTERS;
            $entries = $context['entries']['records'];

            // check if datasource has filters

            if (is_array($filters)) {

                // check if datasource is processed second time

                if ($context['datasource']->secondrun) {

                    // save entries from second run

                    $context['datasource']->secondrun = $entries;

                } else {

                    if (count($entries) === 0) {

                        // run again with default filters
                        // if current language has no results

                        $context['datasource']->dsParamFILTERS = $filters;

                        $context['datasource']->secondrun = true;
                        $context['datasource']->execute($context['datasource']->_param_pool);

                        // fetch entries from second run

                        $context['entries']['records'] = $context['datasource']->secondrun;
                    }
                }
            }
        }
    }


    /**
     * DATA SOURCE POST EXECUTE
     *
     * control the datasource output
     *
     * https://www.getsymphony.com/learn/api/2.3.3/delegates/#DataSourcePostExecute
     *
     * @since version 1.0.0
     */

    public function dataSourcePostExecute($context)
    {
        if (multilingual::$languages && $context['xml'] instanceof XMLElement) {

            // fiend entries & process fields

            $context['xml'] = multilingual::findEntries($context['xml']);
        }
    }
}
