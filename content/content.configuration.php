<?php

if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/multilingual/lib/class.multilingual.php');

class contentExtensionMultilingualConfiguration extends AdministrationPage
{

    /**
     * VIEW
     *
     * build the page that shows all configuration settings for the multilingual
     * extension.
     *
     * http://www.getsymphony.com/learn/api/2.3.3/toolkit/administrationpage/#view
     *
     * @since version 2.0.0
     */

     public function view()
     {
        // PAGE SETUP

        $this->setPageType('form');
        $this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), multilingual::EXT_NAME)));
        $this->appendSubheading(multilingual::EXT_NAME);


        // MESSAGES

        // show warning message if the configuration file is not writable

        if (General::checkFile(CONFIG) === false) {
            $this->pageAlert(
                __('The Symphony configuration file, %s, or folder is not writable. You will not be able to save changes to preferences.', array('<code>/manifest/config.php</code>')),
                Alert::ERROR
            );
        }

        // show warning message if no language is defined

        if (!Symphony::Configuration()->get('languages', 'multilingual')) {
            $this->pageAlert(
                __('You need to define one or more languages to make this extension work as expected.'),
                Alert::ERROR
            );
        }

        // show success message if the configuration was successfully saved

        if (isset($this->_context[0]) && $this->_context[0] == 'success') {
            $this->pageAlert(
                __('Preferences saved.'),
                Alert::SUCCESS
            );
        }


        // FIELDSET #1 "MULTILINGUAL"

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(
            new XMLElement('legend', __('Languages'))
        );

        // create group

        $group = new XMLElement('div', null, array('class' => 'two columns'));


        // #1.1) LANGUAGES

        $column = new XMLElement('div', null, array('class' => 'column'));

        // get "languages" settings from configuration

        $languages = Symphony::Configuration()->get('languages', 'multilingual');
        $languages = str_replace(' ', '',   $languages);
        $languages = str_replace(',', ', ', $languages);

        // add settings for language codes

        $form_languages['input'] = new XMLElement('label', __('Active Languages'), array('for' => 'settings-multilingual-languages'));
        $form_languages['input']->appendChild(
            Widget::Input(
                'multilingual[languages]',
                $languages,
                'text',
                array(
                    'id' => 'settings-multilingual-languages'
                )
            )
        );

        // add help text

        $form_languages['help'] = new XMLElement(
            'p',
            __('Comma-separated list of <a href="http://en.wikipedia.org/wiki/ISO_639-1">ISO 639-1</a> language codes that are accessible by all users.'),
            array('class' => 'help')
        );

        // append to column & group

        $column->appendChildArray($form_languages);
        $group->appendChild($column);


        // #1.2) RESTRICTED LANGUAGES

        $column = new XMLElement('div', null, array('class' => 'column'));

        // get "regions" settings from configuration

        $languages_restricted = Symphony::Configuration()->get('languages_restricted', 'multilingual');
        $languages_restricted = str_replace(' ', '',   $languages_restricted);
        $languages_restricted = str_replace(',', ', ', $languages_restricted);

        // build input field

        $form_languages_restricted['input'] = new XMLElement('label', __('Restricted Languages <i>Optional</i>'), array('for' => 'settings-multilingual-languages-restricted'));
        $form_languages_restricted['input']->appendChild(
            Widget::Input(
                'multilingual[languages_restricted]',
                $languages_restricted,
                'text',
                array(
                    'id' => 'settings-multilingual-languages-restricted'
                )
            )
        );

        // add help text

        $form_languages_restricted['help'] = new XMLElement(
            'p',
            __('Additional list of language codes that are only accessible by logged in authors.'),
            array('class' => 'help')
        );

        // append to column & group

        $column->appendChildArray($form_languages_restricted);
        $group->appendChild($column);

        // append column to fieldset & context

        $fieldset->appendChild($group);
        $this->Form->appendChild($fieldset);


        // FIELDSET #2 "REGIONS"

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(
            new XMLElement('legend', __('Regions'))
        );

        // create group

        $group = new XMLElement('div', null, array('class' => 'two columns'));


        // #2.1) REGIONS

        $column = new XMLElement('div', null, array('class' => 'column'));

        // get "regions" settings from configuration

        $regions = Symphony::Configuration()->get('regions', 'multilingual');
        $regions = str_replace(' ', '',   $regions);
        $regions = str_replace(',', ', ', $regions);

        // add settings for region codes

        $form_regions['input'] = new XMLElement('label', __('Active Regions <i>Optional</i>'), array('for' => 'settings-multilingual-regions'));
        $form_regions['input']->appendChild(
            Widget::Input(
                'multilingual[regions]',
                $regions,
                'text',
                array(
                    'id' => 'settings-multilingual-regions'
                )
            )
        );

        // add help text

        $form_regions['help'] = new XMLElement(
            'p',
            __('Comma-separated list of <a href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2">ISO 3166-1 alpha-2</a> region codes that are accessible by all users.'),
            array('class' => 'help')
        );

        // append to column & group

        $column->appendChildArray($form_regions);
        $group->appendChild($column);


        // #2.2) RESTRICTED REGIONS

        $column = new XMLElement('div', null, array('class' => 'column'));

        // get "regions" settings from configuration

        $regions_restricted = Symphony::Configuration()->get('regions_restricted', 'multilingual');
        $regions_restricted = str_replace(' ', '',   $regions_restricted);
        $regions_restricted = str_replace(',', ', ', $regions_restricted);

        // add settings for region codes

        $form_regions_restricted['input'] = new XMLElement('label', __('Restricted Regions <i>Optional</i>'), array('for' => 'settings-multilingual-regions-restricted'));
        $form_regions_restricted['input']->appendChild(
            Widget::Input(
                'multilingual[regions_restricted]',
                $regions_restricted,
                'text',
                array(
                    'id' => 'settings-multilingual-regions-restricted'
                )
            )
        );

        // add help text

        $form_regions_restricted['help'] = new XMLElement(
            'p',
            __('Additional list of region codes that are only accessible by logged in authors.'),
            array('class' => 'help')
        );

        // append to column & group

        $column->appendChildArray($form_regions_restricted);
        $group->appendChild($column);

        // append column to fieldset & context

        $fieldset->appendChild($group);
        $this->Form->appendChild($fieldset);


        // FIELDSET #3 "ROUTING"

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(
            new XMLElement('legend', __('Routing'))
        );

        // create new group & column

        $group = new XMLElement('div', null, array('class' => 'two columns'));
        $column = new XMLElement('div', null, array('class' => 'column'));


        // #3.1) HTACCESS

        // get "htaccess" settings from configuration

        $htaccess = Symphony::Configuration()->get('htaccess', 'multilingual');
        if (!$htaccess) $htaccess =  'no';

        // add checkbox for htaccess configuration

        $form_htaccess['input'] = Widget::Checkbox(
            'multilingual[htaccess]',
            $htaccess,
            __('Enable htaccess rewrite rules')
        );

        // set checkbox value

        if ($htaccess === 'yes') $form_htaccess['input']->setAttribute('checked', 'checked');

        // add help text

        $form_htaccess['help'] = new XMLElement(
            'p',
            __('This will make the default multilingual url-structure ("<code>/xy/page/</code>") work out of the box (without relying on any further page- or routing-manipulation). Refer to the documentation for further details about routing possibilities.'),
            array('class' => 'help')
        );

        // append to column (including optional error message)

        if (isset($context['errors']['multilingual']['htaccess'])) {
            $column->appendChild(Widget::Error($form_htaccess['input'], $context['errors']['multilingual']['htaccess']));
        } else {
            $column->appendChildArray($form_htaccess);
        }

        // append column to group & create new column

        $group->appendChild($column);
        $column = new XMLElement('div', null, array('class' => 'column'));


        // #3.2) DOMAIN LANGUAGE CONFIGURATION

        // get "domains" settings from configuration

        $domains = Symphony::Configuration()->get('domains', 'multilingual');
        $domains = explode(", ", $domains);
        $domains = implode("\n", $domains);

        // add textarea for domain configuration

        $form_domains['input'] = new XMLElement('label', __('Domain Configuration <i>Optional</i>'), array('for' => 'settings-multilingual-domains'));
        $form_domains['input']->appendChild(
            Widget::Textarea(
                'multilingual[domains]',
                5,
                50,
                $domains,
                array(
                    'id' => 'settings-multilingual-domains'
                )
            )
        );

        // add help text

        $form_domains['help'] = new XMLElement(
            'p',
            __('Define languages for (sub)domains by adding "<code>domain.tld=xy</code>" rules (one per line).<br/>Also works with additional region codes: "<code>domain.ch=de-ch</code>"'),
            array('class' => 'help')
        );

        // append to column & group

        $column->appendChildArray($form_domains);

        // append column to group & fieldset

        $group->appendChild($column);
        $fieldset->appendChild($group);
        $this->Form->appendChild($fieldset);


        // FIELDSET #4 "REDIRECTING"

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(
            new XMLElement('legend', __('Redirecting'))
        );
        $fieldset->appendChild(
            new XMLElement('p', __('Define the behavior of the <br/>multilingual redirect event.'), array('class' => 'help'))
        );

        // create new group & column

        $group = new XMLElement('div', null, array('class' => 'two columns'));
        $column = new XMLElement('div', null, array('class' => 'column'));


        // #4.1) REDIRECT MODE

        // get "redirect" settings from configuration

        $redirect_mode = Symphony::Configuration()->get('redirect_mode', 'multilingual');
        if (!$redirect_mode) $redirect_mode =  '0';

        // set redirect options

        $redirect_mode_options = [
            '0' => __('Always redirect'),
            '1' => __('Only redirect if a matching language is found')
        ];

        // add redirect label

        $form_redirect_mode['label'] = new XMLElement('p', __('Redirect Mode'));

        // add redirect settings (radio buttons)

        foreach ($redirect_mode_options as $key => $value) {

            $label = Widget::Label($value);
            $radio = Widget::Input('multilingual[redirect_mode]', strval($key), 'radio');

            if ($redirect_mode === strval($key)) {
                $radio->setAttribute('checked', '');
            }

            $label->prependChild($radio);
            $form_redirect_mode[$key] = $label;
        }

        // append to column

        $column->appendChildArray($form_redirect_mode);


        // #4.2) REDIRECT REGION

        // get "redirect_county" settings from configuration and define fallback value

        $redirect_region = Symphony::Configuration()->get('redirect_region', 'multilingual');
        if (!$redirect_region) $redirect_region = '0';

        // set redirect options

        $redirect_region_options = [
            '0' => __('Yes'),
            '1' => __('Only if a matching region was detected'),
            '2' => __('No'),
        ];

        // add redirect label

        $form_redirect_region['br'] = new XMLElement('br');
        $form_redirect_region['label'] = new XMLElement('p', __('Should the redirect event also point to a specific region?<br/><span class="help">This option requires at least one region-code in the "<code>Active Regions</code>" field.</span>'));

        // add redirect settings (radio buttons)

        foreach ($redirect_region_options as $key => $value) {

            $label = Widget::Label($value);
            $radio = Widget::Input('multilingual[redirect_region]', strval($key), 'radio');

            if ($redirect_region === strval($key)) {
                $radio->setAttribute('checked', '');
            }

            $label->prependChild($radio);
            $form_redirect_region[$key] = $label;
        }

        // append to column & group

        $column->appendChildArray($form_redirect_region);
        $group->appendChild($column);


        // #4.3) REDIRECT METHOD

        $column = new XMLElement('div', null, array('class' => 'column'));

        // get "redirect_method" from configuration

        $redirect_method = Symphony::Configuration()->get('redirect_method', 'multilingual');
        if (!$redirect_method) $redirect_method =  '0';

        // set redirect_method options

        $redirect_method_options = [
            '0' => __('domain.tld → domain.tld/xy/'),
            '1' => __('domain.tld → domain.tld?language=xy'),
            '2' => __('domain.tld → domain.xy'),
        ];
        
        // add redirect method label

        $form_redirect_method['label'] = new XMLElement('p', __('Redirect Method'));

        // add redirect method settings (radio buttons)

        foreach ($redirect_method_options as $key => $value) {

            $label = Widget::Label($value);
            $radio = Widget::Input('multilingual[redirect_method]', strval($key), 'radio');

            if ($redirect_method === strval($key)) {
                $radio->setAttribute('checked', '');
            }

            $label->prependChild($radio);
            $form_redirect_method[$key] = $label;
        }

        // add help text

        $form_redirect_method['help'] = new XMLElement('p', __('Redirecting to (sub)domains requires a valid <a href="#settings-multilingual-domains">Domain Configuration</a> for the detected language.'));
        $form_redirect_method['help']->setAttribute('class', 'help');

        // append to column/group/fieldset

        $column->appendChildArray($form_redirect_method);

        // append column to group & fieldset

        $group->appendChild($column);
        $fieldset->appendChild($group);
        $this->Form->appendChild($fieldset);


        // FIELDSET #5 "DEBUGGING"

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Debugging')));
        
        // create group

        $group = new XMLElement('div', null, array('class' => 'two columns'));


        // #5.1) DEBUG

        $column = new XMLElement('div', null, array('class' => 'column'));

        // get "debug" settings from configuration

        $debug = Symphony::Configuration()->get('debug', 'multilingual');
        if (!$debug) $debug =  'no';

        // add "debug" checkbox

        $form_debug['input'] = Widget::Checkbox(
            'multilingual[debug]',
            $debug,
            __('Add additional debugging parameters to XML output')
        );

        // set checkbox value

        if ($debug === 'yes') $form_debug['input']->setAttribute('checked', 'checked');

        // add help text

        $form_debug['help'] = new XMLElement(
            'p',
            __('This option adds the following additional parameters to the XML output:<br/><br/><code>multilingual-language-source</code><br/><code>multilingual-region-source</code><br/><code>multilingual-browser-cookie</code><br/><code>multilingual-browser-header</code>'),
            array('class' => 'help')
        );

        // append to column & group

        $column->appendChildArray($form_debug);
        $group->appendChild($column);


        // #5.2) DISABLE COOKIE

        $column = new XMLElement('div', null, array('class' => 'column'));

        // get "cookie_disable" settings from configuration

        $cookie_disable = Symphony::Configuration()->get('cookie_disable', 'multilingual');
        if (!$cookie_disable) $cookie_disable =  'no';

        // add "cookie_disable" checkbox

        $form_cookie_disable['input'] = Widget::Checkbox(
            'multilingual[cookie_disable]',
            $cookie_disable,
            __('Disable multilingual cookie')
        );

        // set checkbox value

        if ($cookie_disable === 'yes') $form_cookie_disable['input']->setAttribute('checked', 'checked');

        // add help text

        $form_cookie_disable['help'] = new XMLElement('p', __("This option disables the multilingual cookie, so it won't be considered in the frontend language/region detection process."));
        $form_cookie_disable['help']->setAttribute('class', 'help');

        // append to column, group & fieldset

        $column->appendChildArray($form_cookie_disable);

        // append to group, fieldset & context

        $group->appendChild($column);
        $fieldset->appendChild($group);
        $this->Form->appendChild($fieldset);


        // #5 ACTIONS

        $actions = new XMLElement('div', null, array('class' => 'actions'));
        $actions->appendChild(Widget::Input('action[save]', __('Save Settings'), 'submit', array('accesskey' => 's')));
        $this->Form->appendChild($actions);
    }


    /**
     * ACTION
     *
     * Perform input validation and save the preferences to the config file.
     * Create/edit/remove multilingual rewrite rules in the htaccess-file.
     *
     * http://www.getsymphony.com/learn/api/2.3.3/toolkit/administrationpage/#action
     *
     * @since version 2.0.0
     */

    public function action()
    {
        // do not proceed if the config file cannot be changed

        if (General::checkFile(CONFIG) === false) {
            redirect(SYMPHONY_URL . multilingual::EXT_CONTENT_PATH);
        }

        if (isset($_POST['action']['save'])) {

            // sanitize input

            $config = filter_var_array($_POST['multilingual'], FILTER_SANITIZE_STRING);

            // validate and clean the language/region-code-input

            $config['languages'] = self::validateCodeString($config['languages']);
            $config['languages_restricted'] = self::validateCodeString($config['languages_restricted']);
            $config['regions'] = self::validateCodeString($config['regions']);
            $config['regions_restricted'] = self::validateCodeString($config['regions_restricted']);

            // transform domain-configuration (each line representing one domain) into array and save domain configuration as comma-separated string

            $config['domains'] = str_replace(' ', '', $config['domains']);
            $config['domains'] = preg_split("/\r\n|\n|\r/", $config['domains']);
            $config['domains'] = implode (", ", $config['domains']);

            // set configuration data

            if (is_array($config) && !empty($config)) {
                Symphony::Configuration()->setArray( array( 'multilingual' => $config), true);
            }

            // save configuration data

            if (Symphony::Configuration()->write()) {

                // clear cache so that the changed data shows up without delay

                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate(CONFIG, true);
                }

                // set htaccess rewrite rules

                if ($config['htaccess'] === 'yes') {

                    // join active and restricted languages/regions to one comma-separated string
                    // at the moment this process assumes there is at least one active language/region if a restriced language/region is set

                    $config_languages = ($config['languages_restricted']) ? $config['languages'] . ', ' . $config['languages_restricted'] : $config['languages'];
                    $config_regions = ($config['regions_restricted']) ? $config['regions'] . ', ' . $config['regions_restricted'] : $config['regions'];

                    // create (blank) set of rules and insert the current set of languages and regions

                    $htaccess_create = multilingual::setHtaccessRewriteRules('create');
                    $htaccess_edit = multilingual::setHtaccessRewriteRules('edit', $config_languages, $config_regions);

                } else {

                    // remove all multlingual htaccess rules

                    $htaccess_remove = multilingual::setHtaccessRewriteRules('remove');

                }

                // check if writing the htaccess-file was successful

                if ($htaccess_create === false || $htaccess_edit === false || $htaccess_remove === false) {

                    // throw an error-message if writing the htaccess-file wasn't successful

                    $this->pageAlert(
                        __('There were errors writing the <code>.htaccess</code> file. Please verify it is writable.'),
                        Alert::ERROR
                    );

                } else {

                    // redirect to configuration page and show success-message

                    redirect(SYMPHONY_URL . multilingual::EXT_CONTENT_PATH . '/success');
                }
            }
        }
    }



    /**
     * VALIDATE CODE STRING
     *
     * Helper function that validates the language/region-code-input
     *
     * @since version 2.0.0
     */

    private function validateCodeString($string)
    {
        // explode string and reduce each single language/region-code to 2 characters

        $codes = explode(',', str_replace(' ', '', $string));

        foreach ($codes as $code) {
            $codes_shortened[] = substr($code, 0, 2);
        }

        // remove any duplicates

        $codes_unique = array_unique($codes_shortened);

        // transform to comma-separated string

        $ret = implode (", ", $codes_unique);

        return $ret;

    }

}