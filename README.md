# SURLS: Simple URL Shortner
SURLS is a simple URL shortner for Apache based on .htaccess and Redirect Directive

### Key Features
 - Single file based and portable
 - Don't need any additional configuration with majority of Apache installatiom
 - Supports click tracking

### Requirement
Apache with support for `.htaccess` and `mod_alias` enabled.

### Installation
- Copy the `surls.php` into your website home directory.
- Open it with a text editor and change the default username/password
- Visit `https://www.example.com/surls.php` to use it

### FAQ

#### What is the URL of aliases created by it?
You can access them via any of these:
- `https://www.example.com/%alias%` (Doesn't support Google Analytics right now)
- `https://www.example.com/surls.php?l=%alias%`
- `https://www.example.com/s/%alias%` (Requires a rewrite rule in .htaccess, see below on how to add it)

#### What are Rewrite Rules required for third type of link?
Add these rules in your `.htaccess` to allow these type of links
```
RewriteEngine on
RewriteRule ^s/(.+)$ surls.php?l=$1 [L]
```

#### How can I add Google Analytics to it
Just open the `surls.php` in a text editor and add Google Property code to it. It will do the rest of work by itself

#### I just want simple tracking, not complex solution like GA
- Create TinyURL aliases for your favorite URL
- Make SURLS points to TinyURL instead of originial URL

Now the redirect flow will be like `https://www.example.com/alias -> https://tinyurl.com/abcde -> https://www.google.com/`

#### Instead of a simple redirect, I want to execute a custom function that will determine the redirect URL
This feature will be updated soon and documentation for it will be available here