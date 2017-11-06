# Multilingual Sections #

The very special approach of this extension (reduce reliance on too many third party extensions, leave as much to core functionality as possible) doesn't require special multilingual field types – you're open to use whichever core or custom fields you like when setting up your multilingual sections.

**Note:** _The [more popular approach][1] to multilingualism in Symphony CMS relies on specific multilingual versions for each different field  type – these [multilingual fields][2] won't be of any use when working with this extension._


## Field settings

Injecting "multilingual behavior" into any (non-multilingual) type of field is as easy as including the same kind of field multiple times (one dedicated to each supported language) and give them the same _base_-handle followed by a dash and the respective language code:

	![IMAGE "Multilingual Sections: Field settings"]

**Note:** _In most cases, it will make sense to mark at least all default language fields as required, while fields that only contain translations can always be marked as optional._


## UI optimization

To provide your clients with a clean and simple user interface, you should use an additional UI extensions like [Parenthesis Tabs][3].

	![IMAGE "Parenthesis Tabs"]

[1]: https://www.getsymphony.com/discuss/thread/95518/
[2]: http://symphonyextensions.com/extensions/?keywords=multilingual+field
[3]: https://github.com/symphonists/parenthesistabs