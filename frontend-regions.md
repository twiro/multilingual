# Frontend Regions #

While **Multilingual** only supports 2-character **language-codes** in its language configuration and the provided `$language`-parameter it also comes with some basic support for **region-codes**.

If an additional region-code is included in the URL (using one of the supported URL structures), found in the multilingual cookie or detected in the browser preferences its value will be provided in a parameter named `$region`.

Unlike the `$language`-parameter which is deeply involved in how **Multilingual** works the `$region`-parameter doesn't come with any built-in logic or superpowers - it's simply meant as an additional information that can be used like any other "normal" parameter.

You also can't define regions in the configuration - any string that consists of 2 alphabetical characters and is provided in the right context (see examples below) will be accepted as region.



## How to set a region

Like with languages **Multilingual** supports 3 methods of setting a region in the URL:

##### 1. Set a region by adding the `$region`-parameter to the URL query string

    domain.com/page/?language=en&region=gb
    
##### 2. Set a region by adding it to the language-code in the URL Path

    domain.com/en-gb/page/

##### 3. Set a region by mapping it to a (sub)domain in Multilingual's domain configuration

    domain.co.uk=en-gb

If none of these methods is used to provide distinct information about a region **Multilingual** will rely on it's own cookie or the browser's `http_accept_language`-header to populate the `$region`-parameter.

**Note:** Unlike the parameter `$language` which will fall back to the default configured language if no valid language-code is found the parameter `$region` will simply be empty if no region can be detected.
