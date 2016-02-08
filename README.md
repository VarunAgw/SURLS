# SURLS: Simple URL Shortener
SURLS is a simple URL shortener written in PHP based on Apache RewriteRule Directive

### Key Features
 - Single file based and portable
 - Don't need any additional configuration with majority of Apache installation

### Requirements
Apache with `mod_rewrite` enabled.

### Installation
- Copy `surls.php` and `.htaccess` into your website home directory.
- Open `surls.php` with a text editor and change the default username/password
- Visit `example.com/surls.php` to use it

### FAQ

#### I want to try it before installing
- Visit http://surls.varunagw.com/ for a demo
- Use username/password as admin/password

#### What is the URL of aliases created by it?
They can be accessed either of these ways:

1. `example.com/%alias%`
2. `example.com/surls/%alias%`
3. `example.com/surls.php?alias=%alias%`

#### Instead of a simple redirect, I want to execute a custom function and optionally redirect
- Create `surls_functions.php` in the same directory which contains surls.php
- Add your function into it
- Make sure to create a dummy alias with the same name that you want to be processed by the function
- You can also download [surls_functions.php](https://github.com/VarunAgw/SURLS/blob/master/surls_functions.php) for a template.

#### I want some basic tracking too!
- Create a [bit.ly](https://bit.ly) aliases for your favorite URL
- Make SURLS alias points to `biy.ly` link instead of the original URL

The redirect flow will be like `example.com/alias -> bit.ly/abc -> boogle.com`
