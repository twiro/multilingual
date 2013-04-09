<?php

    Class Extension_Multilingual extends Extension
    {
        public static $languages, $language;

        // delegates

        public function getSubscribedDelegates()
        {
            return array(

                array('page'     => '/system/preferences/',
                      'delegate' => 'AddCustomPreferenceFieldsets',
                      'callback' => 'addCustomPreferenceFieldsets'),

                array('page'     => '/system/preferences/',
                      'delegate' => 'Save',
                      'callback' => 'saveCustomPreferenceFieldsets'),

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
            // remove settings for language codes

            Symphony::Configuration()->remove('languages', 'multilingual');
            Administration::instance()->saveConfig();
        }

        // preferences

        public function addCustomPreferenceFieldsets($context)
        {
            // add settings for language codes

            $group = new XMLElement('fieldset');
            $group->setAttribute('class', 'settings');

            $children['legend'] = new XMLElement('legend', __('Multilingual'));

            $value = Symphony::Configuration()->get('languages', 'multilingual');

            $children['label'] = new XMLElement('label', __('Languages'));
            $children['label']->appendChild(Widget::Input('settings[multilingual][languages]', $value, 'text'));

            $children['help'] = new XMLElement('p');
            $children['help']->setAttribute('class', 'help');
            $children['help']->setValue(__('Comma-separated list of <a href="http://en.wikipedia.org/wiki/ISO_639-1">ISO 639-1</a> language codes.'));

            $group->appendChildArray($children);

            $context['wrapper']->appendChild($group);
        }

        public function saveCustomPreferenceFieldsets()
        {
            // add settings for language codes

            $value = $_POST['settings']['multilingual']['languages'];
            $value = filter_var($value, FILTER_SANITIZE_STRING);

            Symphony::Configuration()->set('languages', $value, 'multilingual');
            Administration::instance()->saveConfig();
        }

        // path

        public function frontendPrePageResolve($context)
        {
            // only run once

            if (!self::$languages) {

                // get supported languages from config

                if (self::$languages = Symphony::Configuration()->get('languages', 'multilingual')) {

                    self::$languages = explode(',', str_replace(' ', '', self::$languages));

                    // check if current path has language code

                    if (!self::$language && preg_match('/^\/([a-z]{2})\//', $context['page'], $match)) {

                        // check if language is supported

                        if (in_array($match[1], self::$languages)) {

                            // remember language

                            self::$language = $match[1];

                            // remove language code from current path

                            $context['page'] = preg_replace('/^\/' . $match[1] . '\//', '/', $context['page']);

                            // special treatment for root page

                            $context['page'] = ($context['page'] !== '/') ? $context['page'] : null;

                        } else {

                            // fallback to default

                            self::$language = self::$languages[0];
                        }
                    }
                }
            }
        }

        // language detect & parameters

        public function frontendParamsResolve($context)
        {
            $root  = Frontend::Page()->_param['root'];

            // detect language if not already
            // set by path or domainmapper

            if (self::$languages && !self::$language) {

                // detect browser language

                $accept = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

                // check if browser language is available

                if (in_array($accept, self::$languages)) {

                    // set language to browser language

                    self::$language = $accept;

                } else {

                    // set language to default language

                    self::$langauge = self::$languages[0];
                }

                // redirect language

                header('Location: ' . $root . '/' . self::$language . '/'); exit;
            }

            // set params

            Frontend::Page()->_param['languages'] = self::$languages;
            Frontend::Page()->_param['language']  = self::$language;
            Frontend::Page()->_param['root-ml']   = Frontend::Page()->_param['mapped'] === 'yes' ?
                                                    Frontend::Page()->_param['root'] :
                                                    Frontend::Page()->_param['root'] . '/' . self::$language;
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
                            $context['datasource']->execute();

                            // fetch entries from second run

                            $context['entries']['records'] = $context['datasource']->secondrun;
                        }
                    }
                }
            }
        }

        public function dataSourcePostExecute($context)
        {
            $xml = $context['xml'];

            if (self::$languages) {

                // check if entries are grouped

                if ($context['datasource']->dsParamGROUP) {

                    // check if datasource has groups

                    if (($groups = $xml->getChildren()) && is_array($groups)) {

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

                                $xml->replaceChildAt($group_index, $group);
                            }
                        }

                        // replace root element

                        $context['xml'] = $xml;
                    }

                } else {

                    // check if datasource has entries

                    if (($entries = $xml->getChildrenByName('entry')) && is_array($entries)) {

                        // handle entries

                        foreach ($entries as $entry_index => $entry) {

                            // add multilingual elements

                            $entry = self::addElements($entry);

                            // replace entry in root element

                            $xml->replaceChildAt($entry_index, $entry);
                        }

                        // replace root element

                        $context['xml'] = $xml;
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

                    if (preg_match('/-' . self::$languages[0] . '$/', $element_handle)) {

                        // if current language is not default

                        if (self::$language !== self::$languages[0]) {

                            // get current language element handle

                            $element2_handle = preg_replace('/-' . self::$languages[0] . '$/', '-' . self::$language, $element_handle);

                            // get current language element

                            $element2 = $entry->getChildByName($element2_handle, 0);
                        }

                        // create new element handle

                        $element3_handle = preg_replace('/-' . self::$languages[0] . '$/', '', $element_handle);

                        // create new element from
                        // current or default language

                        if ($element2 && str_replace('<![CDATA[]]>', '', trim($element2->getValue()))) {

                            $element3 = new XMLElement($element3_handle, $element2->getValue(), $element2->getAttributes());

                            $element3->setChildren($element2->getChildren());

                        } else {

                            $element3 = new XMLElement($element3_handle, $element->getValue(), $element->getAttributes());

                            $element3->setChildren($element->getChildren());
                        }

                        // add new element to entry

                        $entry->appendChild($element3);
                    }
                }
            }

            return $entry;
        }
    }
