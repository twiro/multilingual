<?php

class Extension_Multilingual extends Extension
{
    private static $languages, $language, $resolved;

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
        Symphony::Configuration()->write();
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

    // detect & redirect language

    public function frontendPrePageResolve($context)
    {
        if (!self::$resolved) {

            // get languages from configuration

            if (self::$languages = Symphony::Configuration()->get('languages', 'multilingual')) {

                self::$languages = explode(',', str_replace(' ', '', self::$languages));

                // detect language from path

                if (preg_match('/^\/([a-z]{2})\//', $context['page'], $match)) {

                    // set language from path

                    self::$language = $match[1];

                } else {

                    // detect language from browser

                    self::$language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
                }

                // check if language is supported

                if (!in_array(self::$language, self::$languages)) {

                    // set to default otherwise

                    self::$language = self::$languages[0];
                }

                // redirect root page

                if (!$context['page']) {

                    header('Location: ' . URL . '/' . self::$language . '/'); exit;
                }
            }

            self::$resolved = true;
        }
    }

    // language detect & parameters

    public function frontendParamsResolve($context)
    {
        if (self::$languages) {

            // set params

            $context['params']['languages'] = self::$languages;
            $context['params']['language']  = self::$language;
        }
    }

    // datasource filtering

    public function dataSourcePreExecute($context)
    {
        // clear preexisting output

        $context['xml'] = null;

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

    // datasource filtering fallback

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

    // datasource output

    public function dataSourcePostExecute($context)
    {
        if (self::$languages && $context['xml'] instanceof XMLElement) {

            // fiend entries & process fields

            $context['xml'] = $this->findEntries($context['xml']);
        }
    }

    // datasource output entries

    private function findEntries(XMLElement $xml, $entries_name = 'entry')
    {
        // check if xml has child elements

        if (($elements = $xml->getChildren()) && is_array($elements)) {

            // handle elements

            foreach ($elements as $element_index => $element) {

                // check if element is xml element

                if ($element instanceof XMLElement) {

                    // check if element is entry

                    if ($element->getName() === $entries_name) {

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

    // datasource output fields

    private function processFields(XMLElement $xml)
    {
        // check if xml has child elements

        if (($elements = $xml->getChildren()) && is_array($elements)) {

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
					 } else {
						 $test = $element->getChildrenByName('item');
						 if (!empty($element->getChildrenByName('item'))) {
							 $this->findEntries($element, 'item');
						 }
					 }
				}

            // check for stored multilingual elements

            if (is_array($multilingual_elements)) {

                // handle multilingual elements

                foreach ($multilingual_elements as $element_handle => $element) {

                    // handle languages

                    foreach (self::$languages as $language) {

                        // check if element exists for each language

                        if (!isset($element[$language]) || !(str_replace('<![CDATA[]]>', '', trim($element[$language]->getValue())) || $element[$language]->getNumberOfChildren())) {

                            // fallback to default language if missing or empty

                            if (isset($element[self::$languages[0]])) {

                                $element[$language] = clone $element[self::$languages[0]];

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
