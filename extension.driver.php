<?php

    Class Extension_Multilingual extends Extension
    {
        public static $languages, $language, $datasource;

        // delegates

        public function getSubscribedDelegates()
        {
            return array(

                array('page'     => '/system/preferences/',
                      'delegate' => 'AddCustomPreferenceFieldsets',
                      'callback' => 'addCustomPreferenceFieldsets'),

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

        // uninstall

        public function uninstall()
        {
            // remove languages from configuration

            Symphony::Configuration()->remove('multilingual');
            Administration::instance()->saveConfig();
        }

        // preferences

        public function addCustomPreferenceFieldsets($context)
        {
            // get languages from configuration

            $languages = Symphony::Configuration()->get('languages', 'multilingual');
            $languages = str_replace(' ', '',   $languages);
            $languages = str_replace(',', ', ', $languages);

            // add settings for language codes

            $group = new XMLElement('fieldset');
            $group->setAttribute('class', 'settings');

            $children['legend'] = new XMLElement('legend', __('Multilingual'));

            $children['label'] = new XMLElement('label', __('Languages'));
            $children['label']->appendChild(Widget::Input('settings[multilingual][languages]', $languages, 'text'));

            $children['help'] = new XMLElement('p');
            $children['help']->setAttribute('class', 'help');
            $children['help']->setValue(__('Comma-separated list of <a href="http://en.wikipedia.org/wiki/ISO_639-1">ISO 639-1</a> language codes.'));

            $group->appendChildArray($children);

            $context['wrapper']->appendChild($group);
        }

        // path

        public function frontendPrePageResolve($context)
        {
            // get languages from configuration

            if (self::$languages = Symphony::Configuration()->get('languages', 'multilingual')) {

                self::$languages = explode(',', str_replace(' ', '', self::$languages));

                // check if current path has language segment

                if (preg_match('/^\/([a-z]{2})\//', $context['page'], $match)) {

                    // check if language is supported

                    if (in_array($match[1], self::$languages)) {

                        // set language

                        self::$language = $match[1];
                    }
                }
            }
        }

        // language detect & parameters

        public function frontendParamsResolve($context)
        {
            if (self::$languages) {

                if (!self::$language) {

                    // detect browser language

                    self::$language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

                    // set to default if browser language not supported

                    if (!in_array(self::$language, self::$languages)) {

                        self::$language = self::$languages[0];
                    }

                    // redirect index

                    if (in_array('index', $context['params']['page-types'])) {

                        header('Location: ' . $context['params']['root'] . '/' . self::$language . '/'); exit;
                    }

                    // add page type

                    $context['params']['multilingual'] = 'no';

                } else {

                    // add page type

                    $context['params']['multilingual'] = 'yes';
                }

                // set params

                $context['params']['languages'] = self::$languages;
                $context['params']['language']  = self::$language;
            }
        }

        // datasource

        public function dataSourcePreExecute($context)
        {
            // check if language preconditions are met

            if (self::$languages && self::$language !== self::$languages[0]) {

                $filters = $context['datasource']->dsParamFILTERS;
                $section = $context['datasource']->getSource();

                // check if datasource has filters

                if (is_array($filters)) {

                    // swap filters to current language fields

                    foreach ($filters as $field_id => $filter) {

                        $field_handle = FieldManager::fetchHandleFromID($field_id);

                        // check if field handle is multilingual

                        if (preg_match('/-' . self::$languages[0] . '$/', $field_handle)) {

                            // get current language field handle

                            $field2_handle = preg_replace('/-' . self::$languages[0] . '$/', '-' . self::$language, $field_handle);

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

        public function dataSourceEntriesBuilt($context)
        {
            // check if language preconditions are met

            if (self::$languages && self::$language !== self::$languages[0]) {

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

        public function dataSourcePostExecute($context)
        {
            if (self::$languages) {

                // check if entries are grouped

                if ($context['datasource']->dsParamGROUP) {

                    // check if datasource has groups

                    if (($groups = $context['xml']->getChildren()) && is_array($groups)) {

                        // handle groups

                        foreach ($groups as $group_index => $group) {

                            // check if group has entries

                            if (($entries = $group->getChildrenByName('entry')) && is_array($entries)) {

                                // handle entries

                                foreach ($entries as $entry_index => $entry) {

                                    // add multilingual elements

                                    $entry = self::addElements($entry);

                                    // replace entry in group

                                    $group->replaceChildAt($entry_index, $entry);
                                }

                                // replace group in root element

                                $context['xml']->replaceChildAt($group_index, $group);
                            }
                        }
                    }

                } else {

                    // check if datasource has entries

                    if (($entries = $context['xml']->getChildrenByName('entry')) && is_array($entries)) {

                        // handle entries

                        foreach ($entries as $entry_index => $entry) {

                            // add multilingual elements

                            $entry = self::addElements($entry);

                            // replace entry in root element

                            $context['xml']->replaceChildAt($entry_index, $entry);
                        }
                    }
                }
            }
        }

        // helper

        private static function addElements(XMLElement $entry)
        {
            // check if entry has elements

            if (($elements = $entry->getChildren()) && is_array($elements)) {

                // handle elements

                foreach ($elements as $element_index => $element) {

                    // get element handle

                    $element_handle = $element->getName();

                    // check if element handle is multilingual

                    if (preg_match('/-([a-z]{2})$/', $element_handle, $match)) {

                        // check if language is supported

                        if (in_array($match[1], self::$languages)) {

                            // remove language segment from element handle

                            $element_handle = preg_replace('/-' . $match[1] . '$/', '', $element_handle);
                            $element_mode   = $element->getAttribute('mode');

                            // set new name and language

                            $element->setName($element_handle);
                            $element->setAttribute('lang', $match[1]);

                            // store element

                            $multilingual_elements[$element_handle . ($element_mode ? ':' . $element_mode : '')][$match[1]] = $element;

                            // remove element

                            $entry->removeChildAt($element_index);
                        }
                    }
                }

                if (is_array($multilingual_elements)) {

                    foreach ($multilingual_elements as $element_handle => $element) {

                        foreach (self::$languages as $language) {

                            // check if element exists for each language

                            if (!isset($element[$language]) || !(str_replace('<![CDATA[]]>', '', trim($element[$language]->getValue())) || $element[$language]->getNumberOfChildren())) {

                                // fallback to default language if missing or empty

                                if (isset($element[self::$languages[0]])) {

                                    $element[$language] = clone $element[self::$languages[0]];

                                    $element[$language]->setAttribute('lang', $language);
                                }
                            }
                        }

                        // readd elements

                        $entry->appendChildArray($element);
                    }
                }
            }

            return $entry;
        }
    }
