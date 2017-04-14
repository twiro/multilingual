# 5. Frontend Language Detection #

**Multilingual** comes with a flexible "Language Detection Stack" that supports different **[URL Structures](#)** and uses the following methods for frontend language detection:

1. **[URL query string](#1-url-query-string)**
2. **[URL Path](#2-url-path)**
3. **[URL (Sub)Domain](#3-url-subdomain)**
4. **[Cookie](#4-cookie)**
5. **[Browser Language](#5-browser-language)**
6. **[Fallback Language](#6-fallback-language)**

With each page request **Multilingual** will execute these methods in chronological order until a **valid language-code** is detected. A language-code is considered "**valid**" if it complies with the formal requirements (two letter-code) and matches one of the frontend languages that have been defined in the **[configuration](#)**.

If no method suceeds in returning a valid match the extension will fall back to it's own default language.


## 1. URL Query String

If the **URL Query string** contains the parameter `language` and this parameter's value is a valid language-code this language-code will be used as frontend language.

##### Example:

    domain.com/page/?language=en

_**Note:** While this is [not the recommended way](https://support.google.com/webmasters/answer/182192) to build your multilingual URLs this method comes handy for debugging and developing as it has the highest priority and therefore will overwrite any of the other language detection mechanisms._

## 2. URL Path

If the **URL path** starts with a valid language-code this language-code will be used as frontend-language.

##### Example:

    /en/page/

This method requires **[page/routing settings](#)** that support this **URL structure**.

_**Note:** This method is the recommended and best supported way to set up multilingual URLs in Symphony CMS._

## 3. URL (Sub)Domain

If the **domain** or **subdomain** is mapped to a valid language-code in the configuration this language-code will be used as frontend-language.

##### Example:

    domain.co.uk/page/
    
This method requires a valid multilingual **[domain configuration](#)**.
 
_**Note:** Language-specific domains (`domain.co.uk`) or subdomains with gTLDs (`de.domain.com`) are considered "good practice" for structuring multilingual content aswell - if relying on the **URL path** is not an option for any reason this method might be the best alternative._

## 4. Cookie

If no valid language can be detected in the **URL** the extension will search for a **cookie** named `multilingual`. If this cookie exists and contains a valid language-code this language-code will be used as frontend-language.

The cookie `multilingual` will be saved whenever one of the 3 URL-based language-detection-methods detect a valid language-code.

_**Note:**  While the availability of a language-cookie offers the possibility to serve multilingual content without a fully-fledged multilingual URL-structure (and rely on the cookie-value once it's set) this is [not recommended](https://support.google.com/webmasters/answer/182192). The cookie is meant to be used for redirecting purposes mainly._

## 5. Browser Language

If the **cookie** `multilingual` is not set or doesn't contain a valid language-code the extension will have a look at the browser settings and check if any of the client's accepted languages defined in the `http_accept_language`-header matches one the frontend languages defined by the extension.

##### Example:

    de-DE,de;q=0.8,en;q=0.7,fr;q=0.4

In case of success this method will always return the "best possbile match" as it scans the accepted languages in order of their relevance (defined by the `q`-parameter in the header-data).


## 6. Fallback Language

If none of the 5 methods above return a valid match the extension will fall back to the configured default language.