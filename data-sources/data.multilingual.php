<?php

require_once(TOOLKIT . '/class.datasource.php');
require_once(EXTENSIONS . '/multilingual/lib/class.multilingual.php');

Class datasourcemultilingual extends Datasource
{

    public function about() {
        return array(
            'name'         => 'Multilingual',
            'author'       => array(
                'name'    => 'Multilingual',
                'website' => 'https://github.com/twiro/multilingual/'
            ),
        'version'      => '2.0.0',
        'release-date' => '2022-02-04',
        'description'  => 'Includes all language- and region-codes provided by the multilingual-extension with additional information about the default and the current language and region.'
        );
    }

    public function allowEditorToParse()
    {
        return false;
    }

    public function execute(array &$param_pool = null)
    {
        // create root "multilingual" node
        $ret = new XMLElement('multilingual');

        // create  "languages" node
        $languages = new XMLElement('languages');

        $i = 0;

        foreach (multilingual::$languages as $lang) {

            // create new "language" node
            $item = new XMLElement('language', $lang);

            // set "handle" attribute
            $item->setAttribute('handle', $lang);

            // get language mode and set "mode" attribute
            $item_mode = (in_array($lang, multilingual::$languages_restricted)) ? 'restricted' : 'active';
            $item->setAttribute('mode', $item_mode);

            // set "default" attribute
            if ($i === 0) $item->setAttribute('default', 'yes');

            // set "current" attribute
            if (multilingual::$language === $lang) $item->setAttribute('current', 'yes');

            $languages->appendChild($item);

            $i++;

        }

        $ret->appendChild($languages);

        if (multilingual::$regions) {

            // create  "regions" node
            $regions = new XMLElement('regions');

            $i = 0;

            foreach (multilingual::$regions as $region) {

                // create new "region" node
                $item = new XMLElement('region', $region);

                // set "handle" attribute
                $item->setAttribute('handle', $region);

                // get language mode and set "mode" attribute
                $item_mode = (in_array($region, multilingual::$regions_restricted)) ? 'restricted' : 'active';
                $item->setAttribute('mode', $item_mode);

                // set "default" attribute
                if ($i === 0) $item->setAttribute('default', 'yes');

                // set "current" attribute
                if (multilingual::$region === $region) $item->setAttribute('current', 'yes');

                $regions->appendChild($item);

                $i++;

            }

            $ret->appendChild($regions);

        }

        return $ret;
    }
}
