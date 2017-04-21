<?php

require_once EXTENSIONS . '/multilingual/lib/class.multilingual.php';

class Extension_Multilingual extends Extension
{
    private static $resolved;

    /**
     * get subscribed delegates
     *
     * @since version 1.0.0
     */

    public function getSubscribedDelegates()
    {
        return array(

            array('page'     => '/system/preferences/',
                  'delegate' => 'AddCustomPreferenceFieldsets',
                  'callback' => 'addCustomPreferenceFieldsets'),

            array('page'     => '/system/preferences/',
                  'delegate' => 'Save',
                  'callback' => 'save'),

            array('page'     => '/frontend/',
                  'delegate' => 'FrontendPrePageResolve',
                  'callback' => 'frontendPrePageResolve'),

            array('page'     => '/frontend/',
                  'delegate' => 'FrontendParamsResolve',
                  'callback' => 'frontendParamsResolve'),

            array('page'     => '/frontend/',
                  'delegate' => 'DataSourcePreExecute',
                  'callback' => 'dataSourcePreExecute'),

            array('page'     => '/frontend/',
                  'delegate' => 'DataSourceEntriesBuilt',
                  'callback' => 'dataSourceEntriesBuilt'),

            array('page'     => '/frontend/',
                  'delegate' => 'DataSourcePostExecute',
                  'callback' => 'dataSourcePostExecute')
        );
    }

    /**
     * enable the extension
     *
     * @since version 2.0.0
     */

    public function enable()
    {
        // get values from configuration (of a possible previous install)

        $languages = Symphony::Configuration()->get('languages', 'multilingual');
        $htaccess = Symphony::Configuration()->get('htaccess', 'multilingual');

        // check if languages are set and htaccess rewrite rules are activated

        if ($languages && $htaccess === 'yes') {

            // create (blank) set of htaccess rewrite rules and insert the current set of languages

            $htaccess_create = self::setHtaccessRewriteRules('create');
            $htaccess_edit = self::setHtaccessRewriteRules('edit', $languages);

        }
        return true;
    }

    /**
     * disable the extension
     *
     * @since version 2.0.0
     */

    public function disable()
    {
        // remove multilingual htaccess rewrite rules

        self::setHtaccessRewriteRules('remove');
    }

    /**
     * update the extension
     *
     * @since version 2.0.0
     */

    public function update($previousVersion = false)
    {
        // Add new default configuration settings when updating from version 1.X

        if (version_compare($previousVersion, '2.0', '<')) {

            Symphony::Configuration()->setArray(
                array(
                    'multilingual' => array(
                        'htaccess' => 'no',
                        'domains' => '',
            			'redirect' => '0',
            			'redirect_method' => '0'
                    )
                ), false
            );
            Symphony::Configuration()->write();
        }

        return true;
    }

    /**
     * uninstall the extension
     *
     * @since version 1.0.0
     */

    public function uninstall()
    {
        // remove multilingual from configuration

        Symphony::Configuration()->remove('multilingual');
        Symphony::Configuration()->write();

        // remove multilingual htaccess rewrite rules

        self::setHtaccessRewriteRules('remove');
    }

    /**
     * Add configuration settings to symphony's preferences page
     *
     * @since version 1.0.0
     */

    public function addCustomPreferenceFieldsets($context)
    {
        // create fieldset #1 "multilingual"

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Multilingual')));

        // create group & column

        $group = new XMLElement('div', null, array('class' => 'column'));
        $column = new XMLElement('div', null, array('class' => 'column'));

        // #1.1) Setting "languages"

        // get "languages" settings from configuration

        $languages = Symphony::Configuration()->get('languages', 'multilingual');
        $languages = str_replace(' ', '',   $languages);
        $languages = str_replace(',', ', ', $languages);

        // add settings for language codes

        $form_languages['input'] = new XMLElement('label', __('Languages'), array('for' => 'settings-multilingual-languages'));
        $form_languages['input']->appendChild(
            Widget::Input(
                'settings[multilingual][languages]',
                $languages,
                'text',
                array(
                    'id' => 'settings-multilingual-languages'
                )
            )
        );

        // add help text

        $form_languages['help'] = new XMLElement('p', __('Comma-separated list of <a href="http://en.wikipedia.org/wiki/ISO_639-1">ISO 639-1</a> language codes.'));
        $form_languages['help']->setAttribute('class', 'help');

        // append to column & group

        $column->appendChildArray($form_languages);

        // append column to fieldset & context

        $group->appendChild($column);
        $fieldset->appendChild($group);
        $context['wrapper']->appendChild($fieldset);

        // create fieldset #2 "multilingual routing"

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Multilingual Routing')));

        // create new group & column

        $group = new XMLElement('div', null, array('class' => 'two columns'));
        $column = new XMLElement('div', null, array('class' => 'column'));

        // #2.1) Setting "htaccess"

        // get "htaccess" settings from configuration

        $htaccess = Symphony::Configuration()->get('htaccess', 'multilingual');
        if (!$htaccess) $htaccess =  'no';

        // add checkbox for htaccess configuration

        $form_htaccess['input'] = Widget::Checkbox(
            'settings[multilingual][htaccess]',
            $htaccess,
            __('Enable htaccess rewrite rules')
        );

        // set checkbox value

        if ($htaccess === 'yes') $form_htaccess['input']->setAttribute('checked', 'checked');

        // add help text

        $form_htaccess['help'] = new XMLElement('p', __('Enabling htaccess rewrite rules will allow for using all defined languages with the default multilingual url-structure ("<code>en/page/</code>") out of the box (without relying on any further page- or routing-manipulation).<br/>Refer to the documentation for further details about routing possibilities.'));
        $form_htaccess['help']->setAttribute('class', 'help');

        // append to column (including optional error message)

        if (isset($context['errors']['multilingual']['htaccess'])) {
            $column->appendChild(Widget::Error($form_htaccess['input'], $context['errors']['multilingual']['htaccess']));
        } else {
            $column->appendChildArray($form_htaccess);
        }

        // append column to group & create new column

        $group->appendChild($column);
        $column = new XMLElement('div', null, array('class' => 'column'));

        // #2.2) setting "Domain Language Configuration"

        // get "domains" settings from configuration

        $domains = Symphony::Configuration()->get('domains', 'multilingual');
        $domains = explode(", ", $domains);
        $domains = implode("\n", $domains);

        // add textarea for domain configuration

        $form_domains['input'] = new XMLElement('label', __('Domain Configuration'), array('for' => 'settings-multilingual-domains'));
        $form_domains['input']->appendChild(
            Widget::Textarea(
                'settings[multilingual][domains]',
                5,
                50,
                $domains,
                array(
                    'id' => 'settings-multilingual-domains'
                )
            )
        );

        // add help text

        $form_domains['help'] = new XMLElement('p', __('Define languages for (sub)domains by adding "<code>domain.tld=xy</code>" rules (one per line)'));
        $form_domains['help']->setAttribute('class', 'help');

        // append to column & group

        $column->appendChildArray($form_domains);

        // append column to group & fieldset

        $group->appendChild($column);
        $fieldset->appendChild($group);
        $context['wrapper']->appendChild($fieldset);

        // create fieldset #3 "multilingual redirect"

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Multilingual Redirect')));

        // create new group & column

        $group = new XMLElement('div', null, array('class' => 'two columns'));
        $column = new XMLElement('div', null, array('class' => 'column'));

        // #3.1) Setting "Redirect"

        // get "redirect" settings from configuration

        $redirect = Symphony::Configuration()->get('redirect', 'multilingual');
        if (!$redirect) $redirect =  '0';

        // set redirect options

        $redirect_options = [
            '0' => __('Always redirect'),
            '1' => __('Only redirect if a matching language is found'),
            '2' => __('Never redirect')
        ];

        // add redirect label

        $form_redirect['label'] = new XMLElement('p', __('Redirect pages that are requested without a valid language code'));
        $form_redirect['label']->setAttribute('class', 'label');
        $form_redirect['br'] = new XMLElement('br');

        // add redirect settings (radio buttons)

        foreach ($redirect_options as $key => $value) {

            $label = Widget::Label($value);
            $radio = Widget::Input('settings[multilingual][redirect]', strval($key), 'radio');

            if ($redirect === strval($key)) {
                $radio->setAttribute('checked', '');
            }

            $label->prependChild($radio);
            $form_redirect[$key] = $label;
        }

        // append to column

        $column->appendChildArray($form_redirect);

        // append column to group & create new column

        $group->appendChild($column);
        $column = new XMLElement('div', null, array('class' => 'column'));

        // #3.2) Setting "Redirect Method"

        // get "redirect_method" from configuration

        $redirect_method = Symphony::Configuration()->get('redirect_method', 'multilingual');
        if (!$redirect_method) $redirect_method =  '0';

        // set redirect_method options

        $redirect_method_options = [
            array('0', $redirect_method === '0', 'domain.tld → domain.tld/xy/'),
            array('1', $redirect_method === '1', 'domain.tld → domain.tld?language=xy'),
            array('2', $redirect_method === '2', 'domain.tld → domain.xy')
        ];

        // add redirect_method settings

        $form_redirect_method['select'] = new XMLElement('label', __('Redirect method'), array('for' => 'settings-multilingual-redirect-method'));
        $form_redirect_method['select']->appendChild(
            Widget::Select(
                'settings[multilingual][redirect_method]',
                $redirect_method_options,
                array(
                    'id' => 'settings-multilingual-redirect-method'
                )
            )
        );

        // add help text

        $form_redirect_method['help'] = new XMLElement('p', __('Redirecting to (sub)domains requires a valid <a href="#settings-multilingual-domains">Domain Configuration</a> for the detected language.'));
        $form_redirect_method['help']->setAttribute('class', 'help');

        // append to column/group/fieldset

        $column->appendChildArray($form_redirect_method);

        // append column to group & fieldset

        $group->appendChild($column);
        $fieldset->appendChild($group);
        $context['wrapper']->appendChild($fieldset);
    }

    /**
     * Perform input validation prior to writing the preferences to the config.
     * Create/edit/remove multilingual rewrite rules in the htaccess-file.
     *
     * @since version 2.0.0
     */

    public function save($context)
    {
        // explode language string and reduce each single language-code to 2 characters (no regions allowed here)

        $langs = explode(',', str_replace(' ', '', $context[settings][multilingual][languages]));

        foreach ($langs as $lang) {
            $langs_shortened[] = substr($lang, 0, 2);
        }

        // remove any duplicates

        $langs_unique = array_unique($langs_shortened);

        // save language configuration as comma-separated string

        $context[settings][multilingual][languages] = implode (", ", $langs_unique);

        // transform domain-configuration (each line representing one domain) into array

        $domains = str_replace(' ', '', $context[settings][multilingual][domains]);
        $domains = preg_split("/\r\n|\n|\r/", $domains);

        // save domain configuration as comma-separated string

        $context[settings][multilingual][domains] = implode (", ", $domains);

        // set htaccess rewrite rules

        if ($context[settings][multilingual][htaccess] === 'yes') {

            // create (blank) set of rules and insert the current set of languages

            $htaccess_create = self::setHtaccessRewriteRules('create');
            $htaccess_edit = self::setHtaccessRewriteRules('edit', $context[settings][multilingual][languages]);

        } else {

            // remove all multlingual rules

            $htaccess_remove = self::setHtaccessRewriteRules('remove');

        }

        // check if writing the htaccess-file was successful - if not throw an error

        if ($htaccess_create === false || $htaccess_edit === false || $htaccess_remove === false) {
            $context['errors']['multilingual'][htaccess] = __('There were errors writing the <code>.htaccess</code> file. Please verify it is writable.');
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

        //

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
     * frontend pre page resolve
     *
     * @since version 1.0.0
     */

    public function frontendPrePageResolve($context)
    {
        if (!self::$resolved) {
            multilingual::getLanguages();
            multilingual::detectFrontendLanguage($context);
            multilingual::redirect($context);
            multilingual::setCookie();
            self::$resolved = true;
        }
    }

    /**
     * frontend params resolve
     *
     * @since version 1.0.0
     */

    public function frontendParamsResolve($context)
    {
        if (multilingual::$languages) {
            $context['params']['language'] = multilingual::$language;
            $context['params']['region'] = multilingual::$region;
            $context['params']['language-source'] = multilingual::$language_source;

            // additional params for testing (!!! TODO : REMOVE BEFORE FINAL RELEASE !!!)

            $context['params']['language-cookie'] = $_COOKIE['multilingual'];
            $context['params']['language-browser'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $context['params']['language-log'] = multilingual::$log;
        }
    }

    /**
     * datasource filtering
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
     * datasource filtering fallback
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
     * datasource output
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
