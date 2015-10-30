# SURLS: Simple URL Shortener
SURLS is a simple URL shortener written in PHP based on Apache .htaccess and RewriteRule Directive

### Key Features
 - Single file based and portable
 - Don't need any additional configuration with majority of Apache installation
 - Supports click tracking

### Requirement
Apache with support for `.htaccess` and `mod_rewrite` enabled.

### Installation
- Copy the `surls.php` into your website home directory.
- Open it with a text editor and change the default username/password
- Visit `example.com/surls.php` to use it

### FAQ

#### What is the URL of aliases created by it?
You can access them via any of these:

1. `example.com/surls.php?alias=%alias%`
2. `example.com/%alias%` (Required a workaround to support alias to custom function mapping, see below)
3. `example.com/r/%alias%` (Requires a custom rewrite rule in .htaccess to enable it, see below)

#### Instead of a simple redirect, I want to execute a custom function and optionally redirect
- Create surls_functions.php in the same directory as surls.php
- Add your function into it
- Download [surls_functions.php](https://github.com/VarunAgw/SURLS/blob/master/surls_functions.php) for a sample

#### FAQ for /%alias%
To support alias to custom function mapping, you need to add dummy link in admin panel for each alias. Point it to any URL, but since custom function have override over that link, your function will execute

#### FAQ for /surls.php?alias=%alias%
It doesn't need any additional code to be added in .htaccess supports all features

#### FAQ for /r/%alias%
It supports alias to custom function mapping without need to add dummy link every time. But you need to add this small script in .htaccess to enable it.
```
RewriteEngine on
RewriteRule ^r/(.+)$ surls.php?alias=$1 [L]
```

#### How can I add Google Analytics to it?
Just open the `surls.php` in a text editor and add Google Property code to it. It will do rest of the work itself. It is not implemented right now.

#### I want simple tracking, not complex solution like GA
- Create [bit.ly](https://bit.ly) aliases for your favorite URL
- Make SURLS URL points to `biy.ly` alias instead of original URL

The redirect flow will be like `example.com/alias -> bit.ly/abc -> boogle.com`