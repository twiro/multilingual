# Multilingual

- Status: experimental
- Version: 1.2
- Release Date: 2013-08-16
- Requirements: Symphony 2.3.3
- Author: Jens Scherbl

Multilingual extension for [Symphony CMS][1].

## Installation and Setup

1.  Upload the "multilingual" folder to your Symphony extensions folder.

2.  Enable it by selecting "Multilingual" under System > Extensions, choose Enable from the with-selected menu, then click Apply.

3.  Go to System > Preferences and provide a comma-separated list of [ISO 639-1][2] language codes in the "Multilingual"-section of your preferences screen.

![Preferences][4]

The first language in your list is considered the default language.

The `$languages` and `$language` parameters will be added to the Page Parameters.

    <params>
        <languages>
            <item handle="en">en</item>
            <item handle="de">de</item>
        </languages>
        <language>en</language>
    </params>

## Sections: Fields

The very special approach of this extension (reduce dependence on too many third party extensions, leave as much to core functionality as possible) doesn't require special multilingual fields.

Just use whatever (non-multilingual) field you like by adding a dedicated field for each language you want to support.

![Sections: Fields][5]

The only requirement for the extension to identify your fields as multilingual is the naming scheme for field handles.

Fields for the same content in different languages have to have the same handle, followed by a dash and the language code as specified in your settings.

Fields for additional languages can always be optional, the extension falls back to the default language for a specific field if no translation is provided.

## Sections: UI

To provide your clients with a clean and simple user interface for adding and editing section entries, you can use additional UI extensions like [Parenthesis Tabs][3].

![Sections: UI][6]

## Data Sources: Filters

You only have to add the fields by which you want to filter your data sources in the default language.

![Data Sources: Filters][7]

The extension takes care of filtering your entries in the current language and falls back to the default language for a specific filter, if no translation is provided.

## Data Sources: Output

Make sure to select all languages for a field in the data source editor's "XML Output"-section.

![Data Sources: Output][8]

The extension removes the language segment from the field name and provides a `lang`-attribute instead.

    <entry>
        <title lang="en" handle="the-extremes-of-good-and-evil">The Extremes of Good and Evil</title>
        <title lang="de" handle="das-hoechste-gut-und-uebel">Das höchste Gut und Übel</title>
    </entry>

In your XSLT, you can now use the `$language`-parameter to get the translation for the current language.

    <xsl:value-of select="title[@lang = $language]" />

[1]: http://getsymphony.com
[2]: http://en.wikipedia.org/wiki/ISO_639-1
[3]: http://symphonyextensions.com/extensions/parenthesistabs/
[4]: https://raw.github.com/jensscherbl/multilingual/master/docs/assets/images/preferences.png
[5]: https://raw.github.com/jensscherbl/multilingual/master/docs/assets/images/sections_fields.png
[6]: https://raw.github.com/jensscherbl/multilingual/master/docs/assets/images/sections_ui.png
[7]: https://raw.github.com/jensscherbl/multilingual/master/docs/assets/images/ds_filters.png
[8]: https://raw.github.com/jensscherbl/multilingual/master/docs/assets/images/ds_output.png