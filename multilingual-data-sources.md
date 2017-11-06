# Multilingual Data Sources #

In general _multilingual_ data sources work exactly the same way as _regular_ data sources do, but there are a few details you should be aware of when you want to use **Multilingual** to it's full capacity.

- **[Filters][1]**
- **[Content][2]**
- **[XML Output][3]**
- **[The "Multilingual" Data Source][4]**


## Filters

Filtering by a field that has been configured as _multilingual_ is as easy as filtering by any other field – you only have to include the field in the **default language** and set up your filter configuration as usual:

	![IMAGE "Multilingual Data Sources: Filters"]

**Multilingual** takes care of filtering your entries in the **current language** and falls back to the **default language** for a specific filter, if no translation is provided – meaning it will do all the background magic needed to work flawlessly together with fully translated url-parameters.


## Content

When setting up your _multilingual_ data sources you need to make sure to include all (required) language-instances of a field in the data source editor's "Content"-section.

	![IMAGE "Multilingual Data Sources: Included Elements "]


## XML Output

The extension removes the language segment from the field name and provides `lang`- and `translated`-attributes instead:

    <entry>
        <title lang="en" translated="yes" handle="united-kingdom">United Kingdom</title>
        <title lang="de" translated="yes" handle="grossbritannien">Großbritannien</title>
    </entry>

In your XSLT, you can now use the `$language`-parameter to get the translation for the current language:

    <xsl:value-of select="title[@lang = $language]"/>

If a translation isn't provided for a specific language, the extension provides a fallback to the default language in your XML output:

    <entry>
        <title lang="en" translated="yes" handle="united-kingdom">United Kingdom</title>
        <title lang="de" translated="no" handle="united-kingdom">United Kingdom</title>
    </entry>

You can check if a field has actually been translated or uses fallback content by testing the `translated`-attribute:

    <xsl:if test="title[@lang = $language]/@translated = 'yes'">
        <xsl:value-of select="title[@lang = $language]"/>
    </xsl:if>


## The "Multilingual" Data Source

**Multilingual** comes with an own data source named "multilingual" that contains the codes of all supported languages as well as information about the **default** and the **current** language:

    <multilingual>
        <language handle="en" default="yes" current="yes">en</language>
        <language handle="de">de</language>
    </multilingual>
    
**Note:** _This data source is mainly meant to help building a language switcher. The basic information about the current language will always be provided by the `$language`-parameter and it's recommended to rely on that source for all your templating logic._


[1]: #filters
[2]: #content
[3]: #xml-output
[4]: #the-multilingual-data-source