<?php

if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

require_once(EXTENSIONS . '/multilingual/lib/class.multilingual.php');

class eventmultilingual_redirect extends Event
{
    /*
     * PRIORITY
     *
     * http://www.getsymphony.com/learn/api/2.3.3/toolkit/event/#kHIGH
     *
     * @since version 2.0.0
     */

    const kHIGH = 3;


    /*
     * ABOUT
     *
     * @since version 2.0.0
     */

    public function about()
    {
        return array(
            'name'         => 'Multilingual Redirect',
            'author'       => array(
                'name'    => 'Multilingual',
                'website' => 'https://github.com/twiro/multilingual/'
            ),
	        'version'      => '2.0.0',
	        'release-date' => '2018-08-04',
	        'trigger-condition' => ''
        );
    }


    /*
     * LOAD
     *
     * @since version 2.0.0
     */

    public function load()
    {
        try {
            return $this->__trigger();
        }
        catch (Exception $ex) {
            if (Symphony::Log()) {
                Symphony::Log()->pushExceptionToLog($ex, true);
            }
        }
    }


    /**
     * DOCUMENTATION
     *
     * http://www.getsymphony.com/learn/api/2.3.3/toolkit/event/#documentation
     *
     * @since version 2.0.0
     */

    public static function documentation()
    {
        return __('This event redirects users to a language-specific version of the current page. Refer to the documentation for further details about redirecting possibilities.');
    }


    /**
     * TRIGGER
     *
     * @since version 2.0.0
     */

    protected function __trigger()
    {
        // abort if frontend does not exist

        if (!class_exists('Frontend', false)) return;

        // abort if the page is in an erroneous state

        if (Frontend::instance()->getException() != null) return;

        // perform redirect

        multilingual::redirect();
    }
}
