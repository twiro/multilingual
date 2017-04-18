# Frontend Redirection #

**Multilingual** offers the possibility to redirect a page request that does not contain frontend language information to another **URL** that does contain this information. If, when a how a redirect is performed can be defined in the "**Multilingual Redirect**" configuration.

![IMAGE]


## Redirect

The first setting will allow you to define **if** and **when** a redirect will be performed:

1. **Always redirect**
2. **Only redirect if a matching language is found**
3. **Never redirect**

While option **1** and **3** seem pretty self-explanatory option **2** requires an understanding of how the [Frontend Language Detection](frontend-language-detection.md) mechanism of **Multilingual** works:

If **Multilingual** can't detect a valid language-code in the **URL** (either by [query string](frontend-language-detection.md/#1-url-query-string), [path](frontend-language-detection.md/#2-url-path) or [domain-configuration](frontend-language-detection.md/#3-url-domain)) it will have no distinct information about which language it should set as frontend-language. To make the best out of this situation and not immediately fall back to the configured default language **Multilingual** knows two more language detection methods that try to "guess" which language could be the best possible match. The first of these "guessing" methods will check for a **[cookie](frontend-language-detection.md/#4-cookie)** that might contain language information from a previous visit, the second one will have a look at the **[browser settings](frontend-language-detection.md/#5-browser-language)** and check if one of the accepted languages matches one of the offered languages.

So what does this mean for our configuration settings?

1. Choosing "**Always redirect**" means **Multilingual** will trigger a redirect, even if neither cookie nor browser settings deliver a matching language. The redirect will use **Multilingual**'s default language as frontend-language.
2. Choosing "**Only redirect if a matching language is found**" means **Multilingual** will only trigger a redirect if a matching language could be detected by either cookie or browser settings.
3. Choosing "**Never redirect**" means **Multilingual** will never trigger a redirect.

**Note:** The idea behind option 2 is to avoid "forcing" visitors into the default language in scenarios where it's unlikely that they're comfortable with that language. Instead they could be offered a list of all available languages to manually choose from.


## Redirect Method

Apart from **when** a redirect will happen you can also define **where** a redirect should point to:  

1. **domain.tld → domain.tld/xy/** (Redirect to [URL path](multilingual-url-structures-and-routing.md))
2. **domain.tld → domain.tld?language=xy** (Redirect to [URL query string](multilingual-url-structures-and-routing.md))
3. **domain.tld → domain.xy** (Redirect to [(sub)domain](multilingual-url-structures-and-routing.md))

**Note:** Redirects will only work as expected if this setting matches the **[URL structure](multilingual-url-structures-and-routing.md)** you're using for your multilingual website.