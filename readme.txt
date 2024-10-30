=== CryoKey ===
Contributors: cryokey
Tags: cryokey sso single sign on multifactor authentication
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: trunk
License: MIT
License URI: http://www.opensource.org/licenses/MIT

Integrate the revolutionary CryoKey single sign-on solution
into WordPress with this simple drop-in plugin.



== Description ==

This is the CryoKey reference plugin for WordPress. We tested
it with WordPress v3.4.1 and v3.5.1, but it should be able to
work on all WordPress v3.0 or higher.

CryoKey is a new single sign-on solution that makes use of
digital certificates. Instead of traditional usernames and
passwords, CryoKey authentication employs public key
cryptography, making authentication simpler and more secure.
Since CryoKey uses existing infrastructure, you don't have
to install any software or buy any hardware. CryoKey makes
generating and using credentials easy - just go to the
CryoKey site and generate as many credentials as you like.
Then, securely log in to sites that recognize CryoKey by
simply selecting an identity with a click.

To encourage others to recognize CryoKey credentials and make
user management more pleasant, we've developed a WordPress
plug-in that takes advantage of the convenience and security
of CryoKey authentication. The new v2.x implementation
utilizes the CryoKey Lite integration model, requiring zero
setup on the server. Just throw the files into the plugins
directory and you're ready to go. To use the more
comprehensive CryoKey integration, you can go back to the
v1.x series.

With this plugin, you log in or out using your CryoKey
certificate. The login page will have a link to log in with
your CryoKey instead of following the standard username and
password flow. Furthermore, the login/logout links throughout
WordPress will automatically attempt a CryoKey login. If
authentication fails (maybe the user doesn't have or refuses
to present a CryoKey), then the user will see the regular
WordPress login prompt.

If you have a valid CryoKey certificate for an unknown user,
the plugin can auto-register new users. Turn on auto-registration
from the administrator's options page (in _Settings_ submenu
_CryoKey_). CryoKey user names are E-Mail based, so the plugin
authenticates users based on their account's E-Mail address.
Auto-registration creates a new account with the CryoKey
credentials E-Mail address and a random password.
Auto-registered screen names will be a random string with a
"CK" prefix; you can change them whenever you want.
Auto-registered user names will be the E-Mail address with a
"CK" prefix.

Starting in v2.2, the plugin supports multi-factor
authentication with CryoKey and the standard WordPress
authentication. You can enable it site-wide in the CryoKey
settings (as an admin), or you can turn it on per-user in the
user profile. With multi-factor authentication, you will need
to enter the appropriate username and password while providing
CryoKey credentials that match the E-Mail address. Users that
need multi-factor authentication must log in by clicking on
the "Log In Using CryoKey" link from the log-in page.

If you're already using CryoKey credentials and want to see it
in action on WordPress, you can visit:
  http://www.authenticade.com/blog

To generate free CryoKeys, visit:
  https://www.cryokey.com

Follow us on Facebook:
  http://www.facebook.com/CryoKey

Follow us on Twitter:
  https://twitter.com/CryoKey

Follow us on Tumblr:
  http://cryokey.tumblr.com/



== Installation ==

Simply copy the following files into your plugins directory
(__wp-content/plugins__):

 * ckicon.png
 * cryokey.php
 * cryokey.js

Then activate the plugin through the _Plugins_ menu in WordPress.

If you want to authenticate using CryoKey, you and your users
may generate them for free at: https://www.cryokey.com



== Changelog ==

v2.4: CryoKey on Android now uses a custom URL scheme. We also use the new Lite Integration Toolkit from the main CryoKey site.

v2.3: Added support for the CryoKey Android client, which does not work with multi-factor yet. As a result, we added an option to disable multi factor authentication.

v2.2: Added initial support for multi-factor (CryoKey with standard username/password) authentication. Also, attempt to flush credentials before logging in and after logging out.

v2.1: If WordPress allows user registrations, then send unknown CryoKey users to registration on failed login (with E-Mail filled in).

v2.0: Use CryoKey Lite integration model for zero configuration setup

v1.3: Allow user to manually change user after CryoKey login (by clicking on the username)

v1.2: Fixed a problem preventing regular login flow for unknown users when auto-login is disabled.

v1.1: Changed account lookups to use "email" instead of "login". Also, added CryoKey options page to change auto-registration settings.

v1.0: Initial Release
