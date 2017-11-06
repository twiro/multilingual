# Frontend Language Detection #

**Multilingual** comes with a flexible "Frontend Language Detection Stack" that supports different **[URL Structures][7]** and uses the following methods for frontend language detection:

1. **[URL query string][1]**
2. **[URL Path][2]**
3. **[URL (Sub)Domain][3]**
4. **[Cookie][4]**
5. **[Browser Language][5]**
6. **[Fallback Language][6]**

With each page request **Multilingual** will execute these methods in chronological order until a **valid language-code** is detected. A language-code is considered "**valid**" if it complies with the formal requirements (two letter-code) and matches one of the frontend languages that have been defined in the **[configuration][8]**.

If no method suceeds in returning a valid match the extension will fall back to it's own default language.


## 1. URL Query String

If the **URL Query string** contains the parameter `language` and this parameter's value is a valid language-code this language-code will be used as frontend language:

    domain.com/page/?language=en


## 2. URL Path

If the **URL path** starts with a valid language-code this language-code will be used as frontend-language:

    domain.com/en/page/


## 3. URL (Sub)Domain

If the **domain** or **subdomain** is [mapped][9] to a valid language-code this language-code will be used as frontend-language:

    domain.co.uk/page/


## 4. Cookie

If no valid language can be detected in the **URL** the extension will search for a **cookie** named `multilingual`. If this cookie exists and contains a valid language-code this language-code will be used as frontend-language.

The cookie `multilingual` will be saved whenever one of the 3 URL-based language-detection-methods detect a valid language-code.

**Note:**  _While the availability of a language-cookie offers the possibility to serve multilingual content without a fully-fledged [multilingual URL-structure][7] (and rely on the cookie-value once it's set) this is [not recommended][11]. The cookie is meant to be used for [redirecting][10] to a multilingual URL, not for replacing it._

## 5. Browser Language

If the **cookie** `multilingual` is not set or doesn't contain a valid language-code the extension will have a look at the browser settings and check if any of the client's accepted languages defined in the `http_accept_language`-header matches one the frontend languages defined by the extension.

    de-DE,de;q=0.8,en;q=0.7,fr;q=0.4

In case of success this method will always return the "best possbile match" as it scans the accepted languages in order of their relevance (defined by the `q`-parameter in the header-data). In case of the example shown above **Multilingual** would choose `en` as frontend language if the language configuration would be set to `fr, en`. 


## 6. Fallback Language

If none of the 5 methods above return a valid match the extension will fall back to the configured default language.


[1]: #1-url-query-string
[2]: #2-url-path
[3]: #3-url-subdomain
[4]: #4-cookie
[5]: #5-browser-language
[6]: #6-fallback-language
[7]: multilingual-url-structures-and-routing.md
[8]: configuration.md
[9]: multilingual-url-structures-and-routing.md/#3-set-a-language-by-mapping-it-to-a-subdomain
[10]: frontend-redirection.md
[11]: https://support.google.com/webmasters/answer/182192
