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
        'version'      => '2.0',
        'release-date' => '2017-11-23',
        'description'  => 'Includes all language-codes provided by the multilingual-extension with additional information about the default and the current language.'
        );
    }

    public function allowEditorToParse()
    {
        return false;
    }

    public function execute(array &$param_pool = null)
    {
        // create root "multilingual" node

        $result = new XMLElement('multilingual');

        $i = 0;

        foreach (multilingual::$languages as $lang) {

            // create new "language" node

            $item = new XMLElement('language', $lang);

            // set "handle" attribute

            $item->setAttribute('handle', $lang);

            // set "default" attribute

            if ($i === 0) $item->setAttribute('default', 'yes');

            // set "current" attribute

            if (multilingual::$language === $lang) $item->setAttribute('current', 'yes');

            $result->appendChild($item);

            $i++;

        }

        return $result;
    }
}
