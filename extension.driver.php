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
     * uninstall the extension
     *
     * @since version 1.0.0
     */

    public function uninstall()
    {
        // remove languages from configuration

        Symphony::Configuration()->remove('multilingual');
        Symphony::Configuration()->write();
    }

    /**
     * Add configuration settings to symphony's preferences page
     *
     * @since version 1.0.0
     */

    public function addCustomPreferenceFieldsets($context)
    {
        // create fieldset

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Multilingual')));

        // create group & column

        $group = new XMLElement('div', null, array('class' => 'two columns'));
        $column = new XMLElement('div', null, array('class' => 'column'));

        // 1) Setting "languages"

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
        $group->appendChild($column);

        // create new column

        $column = new XMLElement('div', null, array('class' => 'column'));

        // 2) setting "Domain Language Configuration"

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
        $group->appendChild($column);

        // append column to fieldset & context

        $fieldset->appendChild($group);
        $context['wrapper']->appendChild($fieldset);

        // create new fieldset

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Multilingual Redirect')));

        // create new group & column

        $group = new XMLElement('div', null, array('class' => 'two columns'));
        $column = new XMLElement('div', null, array('class' => 'column'));

        // 3) Setting "Redirect"

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

        // 4) Setting "Redirect Method"

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
     *
     * @since version 2.0.0
     */

    public function save($context)
    {
        // transform domain-configuration (each line representing one domain) into array

        $domains = str_replace(' ', '', $context[settings][multilingual][domains]);
        $domains = preg_split("/\r\n|\n|\r/", $domains);

        // save domain configuration as comma-separated string

        $context[settings][multilingual][domains] = implode (", ", $domains);
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

            $context['xml'] = $this->findEntries($context['xml']);
        }
    }

    /**
     * datasource output entries
     *
     * @since version 1.0.0
     */

    private function findEntries(XMLElement $xml, $node_name = 'entry')
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

                        $element = $this->processFields($element);

                    } else {

                        // find entries

                        $element = $this->findEntries($element);
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

                    } else if (!empty($element->getChildrenByName('item'))) {

                        $this->findEntries($element, 'item');

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
