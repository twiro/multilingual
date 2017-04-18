# Multilingual URL Structures & Routing #

**Multilingual** supports 3 different methods of **structuring URLs** that contain information about the frontend language:

1. Set a language by **[URL query string](#1-url-query-string)**
2. Set a language in the **[URL path](#2-url-path)**
3. Set a language by mapping it to a **[URL (sub)domain](#3-url-domain)**


## 1. Set a language by URL query string {#1-url-query-string}

This method can easily build open Symphony's URL-structure and doesn't require any further configuration. Simply attach the `language`-parameter to your frontend links, give it the appropriate value and you're done.

    domain.com/page/?language=en

**Note:** While this method comes handy for debugging and developing (as it has the highest priority and therefore will overwrite any of the other language detection mechanisms) it shouldn't be used as URL-structure for a multilingual website in production. Regarding SEO and linkability you're [better off](https://support.google.com/webmasters/answer/182192) with one of the succeeding methods.


## 2. Set a language in the URL path {#2-url-path}

Including the language-code at the beginning of the **URL path** is the common and recommended way of structuring multilingual URLs in Symphony.

    domain.com/en/page

There are different possibilities how to set up **pages** and **routing** for this URL structure and it's up to you which one best suites your workflow:


### 2.1 Using core features only

You can create top-level-pages for each supported language and then use them as parent-pages for all other pages. This might not be the best solution for a website that requires lots of pages (particularly when it comes to support more than 2 languages), but it offers the possibility of setting up a complete multilingual URL structure (including translated page-names and -handles) with nothing more than Symphony's core features.

![IMAGE]


### 2.2 Using Multilingual's htaccess rewrite rules

If you don't want to set up separate pages for each language you could opt for **Multilingual's** **htaccess rewrite rules** which will turn a default non-multilingual page-setup into a fully multilingual one (supporting all offered languages) with just one click - just tick "**Enable htaccess rewrite rules**" in the configuration and you're done.

But while this method is easy to set up and use it doesn't offer an integrated solution for translating page-names and -handles. Meaning only the section-based parts of your URL will be multilingual while the page-based fragments wont't get translated.

I'm aware of this being a weak point and the missing link in making **Multilingual** a real "All-in-one-Solution", so I might actually try to build an integrated solution ony day. Until then have a look at the chapter [Multilingual pages](#) to learn more about the current possibilities of solving this task.


### 2.3 Using a routing extension

If none of the abovementioned methods offers the flexibility you'd like to have you can also use a separate extension to take care of all the routing stuff. **[URL router](https://github.com/symphonists/url_router)** and **[Routing](https://github.com/jensscherbl/routing)** both work well together with **Multilingual** (you can even combine them  with the integrated **htaccess rewrite rules**) and offer the possibility to create fully translated URLs by setting up mutiple routes per page (one for each language).

_(I will do my best to include examples about how to use routing extensions in combination with **Multilingual** soon.)_


## 3. URL (Sub)domain {#3-url-domain}

If you want to avoid language-codes in the **URL path** for any reason your best alternative will be language-specific domains (`domain.co.uk`) or subdomains with gTLDs (`de.domain.com`) to define the language of a frontend page.

**Multilingual** supports this method by allowing you to directly map (sub)domains to specific languages in the **Domain configuration**. Just add a simple `domain.tld=xy`-rule for each domain you want to set a language for and you're ready to go.

[!IMAGE]

## Combining these methods {#combining-methods}

You're not limited to one of these methods with **Multilingual**. The extension will execute the full "[Frontend Language Detection Stack](frontend-language-detection.md)" with every page request and always go with the first valid language-match it is able to detect. This offers the possibility to use a more specific method (like a query string) to overwrite a less specific one (like domain mapping):

#### Example:

In case language configuration would be **`en, de`** and the domain configuration would include **`domain.co.uk=en`** calling the URL **`domain.co.uk/page/?language=de`** would result in **`de`** as frontend language as the language-definition by URL query-string is more specific than the domain-mapping-method. (See [Frontend Language Detection](frontend-language-detection.md) for further details).