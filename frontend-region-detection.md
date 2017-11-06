# Frontend Region Detection #

While **Multilingual** only supports 2-character **language-codes** in its language configuration and the provided `$language`-parameter it also comes with some basic support for **region-codes**.

If an additional region-code is included in the URL, found in the multilingual cookie or detected in the browser preferences its value will be provided in an additional parameter named `$region`.

Unlike the `$language`-parameter which is deeply involved in how **Multilingual** works the `$region`-parameter doesn't come with any built-in logic or special powers – it's simply meant as an additional information that can be used like any other parameter.


## How to set a region

An additional **region-code** can easily be included in all 3 different [URL structures][1] that are supported by **Multilingual**:


##### 1. Set a region by adding the `$region`-parameter to the URL query string

    domain.com/page/?language=en&region=gb
    
##### 2. Set a region by adding it to the language-code in the URL Path

    domain.com/en-gb/page/

##### 3. Set a region by mapping it to a (sub)domain in Multilingual's domain configuration

    domain.co.uk=en-gb


## Autodetection & Fallback

If the URL doesn't provide information about a distinct region **Multilingual** will rely on it's own cookie or the browser's `http_accept_language`-header to populate the `$region`-parameter.

But unlike the indispensable `$language`-parameter  which will fall back to the default configured language if no valid language-code is found the parameter `$region` will simply be empty if no region can be detected.


[1]: multilingual-url-structures-and-routing.md