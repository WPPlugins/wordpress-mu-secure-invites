=== Secure Invites for Wordpress MU ===
Contributors: mrwiblog
Donate link: http://www.stillbreathing.co.uk/donate/
Tags: invite, invitation, secure, lock, email, signup, registration
Requires at least: 2.7
Tested up to: 2.9.1
Stable tag: 0.8.5

Secure Invites is a Wordpress MU plugin that allows you to only allow invited people to sign up.

== Description ==

This plugin stops access to your signup page, except where the visitor has been invited and clicked the link in their invitation email. Your users invite people, and you can see who has sent the most invitations, and how many resulting signups have occurred. Other features:

* Restrict the ability to invite people to users who have been registered only for a certain number of days or more
* View the number of invites sent and resulting signups per month
* View the users who have sent the most invites, and the number of resulting signups
* Browse all invitations sent (auto paginated)
* Change the default email text
* Set after how many days an invitation will expire
* Works with different locations of signup page (default: /wp-signup.php)
* Set the message to show if someone tries to sign up with no valid invitation
* Turn off security on signup form, allowing anyone to sign up (this does not affect the ability to onvite people)

This plugin is based on the invitation plugin by kt (Gord) from http://www.ikazoku.com.

== Installation ==

The plugin should be placed in your /wp-content/mu-plugins/ directory (*not* /wp-content/plugins/) and requires no activation.

To enable the template form in your template page you should call the secure\_invite\_form() function like this:

&lt;php? secure\_invite\_form(); ?&gt;

There are three optional parameters in this function, they are:

1. The CSS class to be applied to the form
2. The message to be shown when the invitation has been successfully sent (by default this is '&lt;p class=&quot;success&quot;&gt;Thanks, your invitation has been sent&lt;/p&gt;')
3. The message to be shown when the invitation could not be sent (by default this is '&lt;p class=&quot;error&quot;&gt;Sorry, your invitation could not be sent&lt;/p&gt;')

So to set the CSS class of the form to 'inviteform', and the success message to 'Yay!' and the error message to 'Oops!' you would use this:

&lt;php? secure\_invite\_form( 'inviteform', 'Yay!', 'Oops!' ); ?&gt;

== Frequently Asked Questions ==

= Why did you write this plugin? =

To scratch my own itch when developing [Wibsite.com](http://wibsite.com "The worlds most popular Wibsite"). Hopefully this plugin helps other developers too.

= Does this plugin work with BuddyPress? =

Yes, several users have reported it works fine. You just need to change the URL of the signup form from the default (wp-signup.php) to the BuddyPress page so the plugin knows which URL to secure.

An invitation form can be easily put into your template page, look at the Installation details for more information.

== Screenshots ==

1. The users invitation form
2. The admin reports
3. The admin settings page

== Changelog ==

0.8.5 Added registration email URL to settings
0.8.4 Allowed multiple registration URLs to be protected, changed email headers to fix from address bug
0.8.3: Added a support link and donate button
0.8.1: Added HTML comments for reasons why a user cannot send invites to help with troubleshooting
0.8 Added stripslashes() to fix display errors (thanks to Mark from http://of-cour.se/ for reporting that)
0.7 Disabled invite form if site registrations have been disabled (thanks to Mark of http://of-cour.se/ for the suggestion)
0.6 Added limit to the number of invitations a user can send, added secure\_invite\_form() function for display in a theme page, added deletion of invites for site admins, cleaned up the architecture

== To-do ==

Next on the list for this plugin is the ability to invite multiple people at the same time (with the same message).

Then, adding the ability for site admins to only allow hand-picked users to send invitations (thanks to Tuomas for that suggestion here: http://www.stillbreathing.co.uk/blog/2009/01/14/wordpress-mu-plugin-secure-invites/#comment-24240).