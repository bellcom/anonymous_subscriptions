ANONYMOUS SUBSCRIPTIONS
----------------

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Implementation
 * Integration (with other modules)
 * Installation and configuration
 * Useful Resources
 * Troubleshooting (known issues)
 * Similar modules
 * Developers: Extending the module
 * Future developments
 * Contributions are welcome!!
 * Credits / Contact


INTRODUCTION
------------

The Anonymous Subscriptions module allows a non-registered user to your site
the ability to register for email notifications to certain pre-defined content types.

These notifications are optional on the pre-defined content types with a flag 
displayed as to whether to send e-mails or not.

There is currently only one subscription list per site, future functionality will add 
the ability for users to subscribe per content type, and/or per node.


FEATURES
--------

This module allows you to specify:
 * If emails should be verified.
 * Should emails be sent by default.
 * Number of emails to send at any one time.
 * Subject line template.
 * Body template.
 * Which content types should have the ability to send notifications.


IMPLEMENTATION
--------------

This module hooks into the node edit/create form to give the user the option
to send a notification to users once that node becomes published.

User emails are stored in a database table, along with any emails that are
pending to be sent.


INTEGRATION (WITH OTHER MODULES)
--------------------------------

Integration is available with the tokens module, and the workbench_moderation
module.


INSTALLATION AND CONFIGURATION
------------------------------

0 - Prerequisites:
N/A

1 - Download the module and copy it into your contributed modules folder:
[for example, your_drupal_path/sites/all/modules] and enable it
from the modules administration/management page.

2 - Configuration:
After successful installation, browse to the Anonymous Subscriptions configuration
page, and configure as per how you would like it to operate.
After this visit the blocks page (admin/structure/blocks) and configure the
Anonymous Subscriptions email block on your site. Your ready to go!


USEFUL RESOURCES
----------------

N/A


TROUBLESHOOTING
------------------------------------

There is an issue with using certain tokens. In particular one I found easily
that had issues was [node:body:?].  When this was used in the email tenplate
no replacement was performed. A workaround for this would be to use
[node:summary].


SIMILAR MODULES
------------------------------------

There are a handful of similar modules out their that do a similar thing, but not 
the same.
They include:
 * [Subscriptions](https://www.drupal.org/projects/subscriptions)
 * [Total Subscription](https://www.drupal.org/projects/total_subscription)
 * [Newsletter](https://www.drupal.org/projects/newsletter)
 * [Simple News](https://www.drupal.org/projects/simplenews)
 
What Anonymous Subscriptions does differently is the ability for an anonymous user 
to register and also be required to verify their email address.

This module also allows the modification of those messages through configuration, 
and also theme templates.


DEVELOPERS: EXTENDING THE MODULE
--------------------------------

Comments, discussions, examples, cases, issues or patches related with
implementations of module's API hooks would be highly welcome and greatly
appreciated.

Theme developers can take a copy of templates/anonymous-subscriptions-message.tpl.php 
and add it to their own theme to modify it how they see fit.


FUTURE DEVELOPMENTS
-------------------

Drupal 8 work.


CONTRIBUTIONS ARE WELCOME!!
---------------------------

Feel free to follow up in the issue queue for any contributions, bug
reports, feature requests.
Tests, feedback or comments in general are highly appreciated.


CREDITS / CONTACT
-----------------

Thanks to Sean (wiifm) for his assistance and feedback in creating this module!

Currently maintained by Paul Kilpatrick (pkil); all initial development,
documentation and testing by Paul Kilpatrick.
