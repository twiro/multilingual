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
            new XMLElement('legend', __('Multilingual Parameters'))
        );

        // create group

        $group = new XMLElement('div', null, array('class' => 'two columns'));


        // #1.1) Setting "languages"

        $column = new XMLElement('div', null, array('class' => 'column'));

        // get "languages" settings from configuration

        $languages = Symphony::Configuration()->get('languages', 'multilingual');
        $languages = str_replace(' ', '',   $languages);
        $languages = str_replace(',', ', ', $languages);

        // add settings for language codes

        $form_languages['input'] = new XMLElement('label', __('Languages'), array('for' => 'settings-multilingual-languages'));
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
            __('Comma-separated list of <a href="http://en.wikipedia.org/wiki/ISO_639-1">ISO 639-1</a> language codes.'),
            array('class' => 'help')
        );

        // append to column & group

        $column->appendChildArray($form_languages);
        $group->appendChild($column);


        // #1.2) Setting "countries"

        $column = new XMLElement('div', null, array('class' => 'column'));

        // get "countries" settings from configuration

        $countries = Symphony::Configuration()->get('countries', 'multilingual');
        $countries = str_replace(' ', '',   $countries);
        $countries = str_replace(',', ', ', $countries);

        // add settings for country codes

        $form_countries['input'] = new XMLElement('label', __('Countries <i>Optional</i>'), array('for' => 'settings-multilingual-countries'));
        $form_countries['input']->appendChild(
            Widget::Input(
                'multilingual[countries]',
                $countries,
                'text',
                array(
                    'id' => 'settings-multilingual-countries'
                )
            )
        );

        // add help text

        $form_countries['help'] = new XMLElement(
            'p',
            __('Comma-separated list of <a href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2">ISO 3166-1 alpha-2</a> country codes.'),
            array('class' => 'help')
        );

        // append to column & group

        $column->appendChildArray($form_countries);
        $group->appendChild($column);

        // append column to fieldset & context

        $fieldset->appendChild($group);
        $this->Form->appendChild($fieldset);


        // FIELDSET #2 "MULTILINGUAL ROUTING"

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(
            new XMLElement('legend', __('Multilingual Routing'))
        );

        // create new group & column

        $group = new XMLElement('div', null, array('class' => 'two columns'));
        $column = new XMLElement('div', null, array('class' => 'column'));


        // #2.1) Setting "htaccess"

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


        // #2.2) setting "Domain Language Configuration"

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
            __('Define languages for (sub)domains by adding "<code>domain.tld=xy</code>" rules (one per line).<br/>Also works with additional country codes: "<code>domain.ch=de-ch</code>"'),
            array('class' => 'help')
        );

        // append to column & group

        $column->appendChildArray($form_domains);

        // append column to group & fieldset

        $group->appendChild($column);
        $fieldset->appendChild($group);
        $this->Form->appendChild($fieldset);


        // FIELDSET #3 "MULTILINGUAL REDIRECT"

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(
            new XMLElement('legend', __('Multilingual Redirect'))
        );
        $fieldset->appendChild(
            new XMLElement('p', __('Define the behavior of the <br/>multilingual redirect event.'), array('class' => 'help'))
        );

        // create new group & column

        $group = new XMLElement('div', null, array('class' => 'two columns'));
        $column = new XMLElement('div', null, array('class' => 'column'));


        // #3.1) Setting "Redirect Mode"

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


        // #3.2) Setting "Redirect Country"

        // get "redirect_county" settings from configuration and define fallback value

        $redirect_country = Symphony::Configuration()->get('redirect_country', 'multilingual');
        if (!$redirect_country) $redirect_country = '0';

        // set redirect options

        $redirect_country_options = [
            '0' => __('Yes'),
            '1' => __('Only if a matching country was detected'),
            '2' => __('No'),
        ];

        // add redirect label

        $form_redirect_country['br'] = new XMLElement('br');
        $form_redirect_country['label'] = new XMLElement('p', __('Should the redirect event also point to a specific country?<br/><span class="help">This option requires at least one country-code in the "<code>countries</code>" field.</span>'));

        // add redirect settings (radio buttons)

        foreach ($redirect_country_options as $key => $value) {

            $label = Widget::Label($value);
            $radio = Widget::Input('multilingual[redirect_country]', strval($key), 'radio');

            if ($redirect_country === strval($key)) {
                $radio->setAttribute('checked', '');
            }

            $label->prependChild($radio);
            $form_redirect_country[$key] = $label;
        }

        // append to column & group

        $column->appendChildArray($form_redirect_country);
        $group->appendChild($column);


        // #3.3) Setting "Redirect Method"

        $column = new XMLElement('div', null, array('class' => 'column'));

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
                'multilingual[redirect_method]',
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
        $this->Form->appendChild($fieldset);


        // FIELDSET #4 "MULTILINGUAL DEBUG"

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Multilingual Debug')));
        
        // create group

        $group = new XMLElement('div', null, array('class' => 'two columns'));


        // #4.1) Setting "debug"

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
            __('This option adds the following additional parameters to the XML output:<br/><br/><code>multilingual-language-source</code><br/><code>multilingual-country-source</code><br/><code>multilingual-browser-cookie</code><br/><code>multilingual-browser-header</code>'),
            array('class' => 'help')
        );

        // append to column & group

        $column->appendChildArray($form_debug);
        $group->appendChild($column);


        // #4.2) Setting "cookie_disable"

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

        $form_cookie_disable['help'] = new XMLElement('p', __("This option disables the multilingual cookie, so it won't be considered in the frontend language/country detection process."));
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

            // validate and clean the language/country-code-input

            $config[languages] = self::validateCodeString($config[languages]);
            $config[countries] = self::validateCodeString($config[countries]);

            // transform domain-configuration (each line representing one domain) into array and save domain configuration as comma-separated string

            $config[domains] = str_replace(' ', '', $config[domains]);
            $config[domains] = preg_split("/\r\n|\n|\r/", $config[domains]);
            $config[domains] = implode (", ", $config[domains]);

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

                if ($config[htaccess] === 'yes') {

                    // create (blank) set of rules and insert the current set of languages and countries

                    $htaccess_create = multilingual::setHtaccessRewriteRules('create');
                    $htaccess_edit = multilingual::setHtaccessRewriteRules('edit', $config[languages], $config[countries]);

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
     * Helper function that validates the language/country-code-input
     *
     * @since version 2.0.0
     */

    private function validateCodeString($string)
    {
        // explode string and reduce each single language/country-code to 2 characters

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