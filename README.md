# Multilingual

Multilingual extension for [Symphony][1].

## Configuration

Go to System › Preferences and provide a comma-separated list of [ISO 639-1][2] language codes in the "Multilingual"-section of your preferences screen.

![Preferences][4]

The first language in your list is considered the default language.

The `$languages` and `$language` parameters will be added to your XML output.

    <params>
        <languages>
            <item handle="en">en</item>
            <item handle="de">de</item>
        </languages>
        <language>en</language>
    </params>

## Sections: Fields

The very special approach of this extension (reduce reliance on too many third party extensions, leave as much to core functionality as possible) doesn't require special multilingual field types.

Use whatever (non-multilingual) field type you like by adding a dedicated field for each of your supported languages.

![Sections: Fields][5]

You just need to make sure that each translation of field shares the same field handle, followed by a dash and the language code as specified in your settings.

In most cases, it's recommended to mark at least all fields for the default language as required.

Translations for a field can always be optional.

If a translation isn't provided for a specific language, the extension falls back to the default language in your XML output.

## Sections: UI

To provide your clients with a clean and simple user interface, you should use an additional UI extensions like [Parenthesis Tabs][3].

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

[1]: //getsymphony.com
[2]: //en.wikipedia.org/wiki/ISO_639-1
[3]: //github.com/hananils/parenthesistabs
[4]: //raw.githubusercontent.com/jensscherbl/multilingual/gh-pages/assets/images/preferences.png
[5]: //raw.githubusercontent.com/jensscherbl/multilingual/gh-pages/assets/images/sections_fields.png
[6]: //raw.githubusercontent.com/jensscherbl/multilingual/gh-pages/assets/images/sections_ui.png
[7]: //raw.githubusercontent.com/jensscherbl/multilingual/gh-pages/assets/images/ds_filters.png
[8]: //raw.githubusercontent.com/jensscherbl/multilingual/gh-pages/assets/images/ds_output.png
