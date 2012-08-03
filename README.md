silent-message
==============

Private Message System based on CodeIgniter Framework with HMVC extension and TankAuth library converted to HMVC modules.
CSS and colors based on CodeIgniter From Scratch and Colors on The Web.


===> LOGIN MODULE
* Customizable auth options (login, logout, register).
* Using phpass (v. 0.3) library for password hashing (instead of unsafe md5 or SHA-1).
* Counting users login attempts. To prevent bruteforce attack (optional). Failed login attempts are determined by IP and by username.
* Logging last login IP-address and time (optional).
* CAPTCHA (builtin Codeginieter and reCAPTCHA) for registration and repetitive login attempt (optional).
* Username is optional, only email is obligatory.
* "Remember me" option.
* Login using username, email address or both (depending on config settings).
* Forgot password (letting users pick a new password upon reactivation).

In nutshell all TankAuth goodness with in HMVC model.

===> REGISTER MODULE
* Registration is instant or after activation by email (optional).
* Email or password can be changed even before account is activated.
* HTML or plain-text emails

===>DASHBOARD MODULE
* Change password or email for registered users.
* Send message to other users (attachment optional).
* Read message, write reply.
* 

===> CONFIG & LANGUAGE FILE
* Most of the features are optional and can be tuned or switched-off in well-documented config file.
* All messages strings are stored in language files. So you can add support for your native language.

TODO:
* Full Language file support (90% done).
* Move CAPTCHA to separated module.

===>SECURITY
* Cross-site request forgery protection.
* XSS Global Protection and overall sanitize ... just in case.
* PhpPass for password hashing (with uniqe, machine depended option).


 
Kudos to:
* http://betterphp.co.uk/home.html
* http://codeigniter.com
* https://bitbucket.org/wiredesignz/codeigniter-modular-extensions-hmvc/wiki/Home
* http://net.tutsplus.com/articles/news/codeigniter-from-scratch-day-1/
* https://github.com/ilkon/Tank-Auth/
* http://www.colorsontheweb.com/colorwheel.asp

