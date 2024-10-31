=== QA Analytics - with Heatmaps & Replay, Privacy Friendly ===
Contributors: QuarkA
Tags: analytics,cookieless,gdpr,heatmap,statistics
Tested up to: 6.6.0
Requires at least: 5.6
Stable tag: 4.1.2.0
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Empower your WordPress site with cookieless analytics, ensuring GDPR/CCPA compliance with QA Analytics.

== Description ==

Are you aware that GA4 is unable to analyze users who do not consent to cookies?
Complying with GDPR regulations, imagine this scenario: should 70% of users decline cookie consent, GA4 would lack data on 70% of its users. Additionally, numerous browsers are bolstering their tracking prevention features, particularly against external servers such as GA4. 
Do you think there's no viable alternative to GA4? Wouldn't you like to ascertain the actual figures?
QA Analytics resolves these concerns by providing a web analytics tool that can collect data without needing cookie consent, all while remaining compliant with GDPR/CCPA regulations.

## QA ANALYTICS: PRIVACY-CENTERED WEB ANALYTICS PLUGIN

Unlock the full potential of web analytics without compromising user privacy. QA Analytics is the go-to WordPress plugin for insightful, real-time analytics, fully compliant with GDPR/CCPA regulations.

From small blogs to large e-commerce platforms, QA Analytics enhances your site's performance while keeping user data private. Our plugin has been empowering websites worldwide to understand their traffic, improve user experience, and boost SEO rankings securely and responsibly.

### WHY CHOOSE QA ANALYTICS?

Dive into the world of analytics where privacy meets efficiency. QA Analytics offers a suite of tools designed for all, from novice bloggers to seasoned webmasters. Here's why QA Analytics stands out:

* **Privacy-focused:** Collects data without cookies, ensuring full compliance with GDPR/CCPA.
* **Real-time insights:** Monitor visitor behavior as it happens on your site.
* **Advanced heatmaps and session replays:** Understand how users interact with your pages.
* **SEO improvement:** Leverage integrated SEO tools and Google Search Console data.
* **Server-side storage:** Data is stored on your server, eliminating third-party access.

### ELEVATE YOUR ANALYTICS WITH QA ANALYTICS

#### EASY INSTALLATION AND SETUP

Get started in minutes! QA Analytics is designed for easy installation and intuitive setup, making web analytics accessible to everyone, regardless of technical expertise.

#### COMPREHENSIVE REPORTING

Visualize your data with beautifully crafted reports. From traffic trends to detailed SEO analysis, QA Analytics brings clarity and insight into your website's performance.

#### HEATMAPS FOR EVERYONE

Understand clicks, scrolls, and interactions with our heatmap tool. Identify hotspots and optimize your site design for better user experience.

#### Concern about "personal data" of your visitors?

*QA Analytics is __NOT cloud-based__.*
*Collected data will be saved __on YOUR host server__.*

In recent years, the use of cloud-based access analysis tools has become a risk. Storing data related to personal information outside of the country could be illegal, as you may know about that the GDPR ruling against Google Analytics.
Because the data will be stored 'in-house' with QA Analytics, there is no worry about that extra risk due to the GDPR or other privacy laws.

Also:
* NO third-party cookies.
* Doesn't record data which someone can identify as personal information like IP address.
* Doesn't pursue visitors.
* Doesn't depend on any outside data resources.


#### Don't need to abandon your database area.

QA Analytics will temporarily use your WordPress database field, but all data will be saved in file format on the disc space of your server. The old data in the database will be deleted sequentially.


#### No need to write tags in code.

Unlike GA4, events data will also be collected without setting tags.
Just activating the plugin, it will start to collect and record data automatically.


### *View intuitively and profoundly*

#### QA Analytics provides:

* Realtime ... See someone visiting your website 'right now'. 
 + Counter of active users in the last minute
 + Session information and session replays ( comes up in a list as long as a session ends )

* Analytics Reports ... Check and analyze your site visits during a particular period.
 + Overview / Statistics
 + Audience
 + Acquisition
 + Behavior (Site Content)
 + Conversions (Goals) 

* Heatmaps on *all* pages ... Get to know visitors' aspects and events on your website page. Experience this well-built map. 
 + Attention Map
 + Click Heatmap / Click-Count Map
 + Scroll Map

* Session Replay across *all* pages ... Follow and trail users' action, by playback like a video.

* SEO Analysis ... Connect with Google API and monitor how search engines impact your site.
 + Search Performance / Rankings
 + Goal Completion from landing pages

* PV Data Download ... Download page view data in TSV format as a detailed log.

* Looker Studio Connector


### Important Notes

Please do NOT compress or minify JavaScript. 
If JavaScript is compressed or minified, JavaScript doesn't run properly and QA Analytics will be unable to collect data. Unexpected errors may occur, too.
For more about -> [Supported Environments](https://mem.quarka.org/en/manual/supported-environments/) 

If you use a specially-configured server or an exclusive server such as AWS, please refer to the linked page below. You may need to consider and change the configuration.
->[Points to note when installing](https://mem.quarka.org/en/manual/points-to-note-when-installing-especially-on-exclusive-servers-such-as-aws/)

The major data processing occurs overnight. (Cron jobs and processing programs run nightly in your time zone.)
Therefore, there will be some load on your server. The larger amount of data, the heavier it gets; and the longer it takes to process.
If your site has a lot of visits and you have other programs that run during night on your server, it would be necessary to consider. We are happy to support if you are unsure about that (possibly charged). Please contact us.


#### Other Features

* Made in Japan :)


== Installation ==

#### Easy to Setup
1. Login to your WordPress admin, go to 'Plugins'.
2. Click 'Add New' and search for “QA Analytics”.
3. Click 'Install Now', then 'Activate' the plugin.
4. QA Analytics will soon collect the data of your website automatically.



== Frequently Asked Questions ==

= Is there a limit to the number of pages for the Heatmap?  =

No, you can view and check heatmap of ALL pages.

= Does it count bot data? =

No. Major bots, such as Google bots, are of course not counted. 
However, malicious bots are increasing day by day. If you want to exclude them strictly, using a plugin to prevent bots is one of the choices.

= What should I know when I use together with a cache plugin? =

* Some cache plugins compress or rewrite JavaScript automatically. In that case, QA-measurement-tag will not work properly and data will not be retrieved. To prevent this from happening, you may need to set up the cache plugin not to do so.
* QA Analytics uses nonce values as a security measure, and accesses to caches that are more than 24 hours old are excluded from the measurement. Setting the page cache lifetime to 10 hours or less is preferable.
* Some cache plugins create a cache even for bot access. QA does not record bot access. Then, while the cache is being accessed, QA will assume that it is a bot access and will not record it.

= Do visits by the administrator count? =

No. Visits by people who logged into WordPress admin are not counted.
If you want to exclude your own visits, login the WordPress admin dashboard first, then visit your webpage.

= What is "Qusuke"? Why Quokka? =

[Qusuke](https://quarka.org/q-suke/) is the mascot of QA Analytics.
It's not a squirrel, nor a mouse. He is a quokka, the world's happiest animal.
Instead of having a frown while doing web analytics, we want everyone to be happy with it like quokkas smile. Qusuke symbolizes that.
You see, QuokkA is one of where "QA" comes from. 

= Can I get the support in English? =

Yes, let us do our best. We really appreciate your generosity in understanding that we are not native English speakers:)

= I can and am willing to contribute to development or translation. Where can I contact you? =

That would be wonderful! Please contact us via [QA Analytics Contact Form (Google Forms)](https://docs.google.com/forms/d/e/1FAIpQLSfP5b3-l73Hmq8WUf7i0cQI3kKA8uFycFeXo8bUqwplZRDUQA/viewform?usp=sf_link) 
We will contact back and look forward to *meeting* you.


== Screenshots ==

1. Home | Dashboard (upgraded version)
2. Realtime
3. Heatmap
4. Overview
5. Goals (upgraded version)
6. SEO Analysis


== Changelog ==

= 4.1.2.0 =

* **Release Date:** October 24, 2024
* Fixed various bugs, handled plugin security, and improved overall performance.
* Addressed minor issues and refined plugin functionality.

= 4.1.1.1 =

* **Release Date:** August 29, 2024
* Fixed a bug where the number of data measurements significantly decreased in some environments.

= 4.1.1.0 =

* **Release Date:** August 27, 2024
* **Improvements and Changes:**
  - Added a "Heatmap" link to the "Growing Landing Page" table.
  - Fixed bugs in menu displays for plugin-specific permissions.
  - Made various minor fixes and UI improvements.

= 4.1.0.0 =

* **Release Date:** July 11, 2024
* **New Features and Improvements:**
  - Added the option to set goals using "Link Click Event with Regular Expressions."
  - Made it possible to create QA Analytics-specific users, selectable as a WordPress user role.
* **Other Changes:**
  - Monthly page view measurement limit raised to 10,000.
  - Made various minor fixes and UI improvements.

= 4.0.1.1 =

* **Release Date:** April 26, 2024
* **Improvements and Changes:**
  - Fixing an issue where heatmap icons are not clickable under specific conditions.

= 4.0.1.0 =

* **Release Date:** April 26, 2024
* **Improvements and Changes:**
  - Basic PV limit has been set to 3,000PV.
  - Fixed a bug where the heatmap viewing page wouldn't open from the link in the report unless a goal was set.
  - Improved Google API integration to allow connection with both "URL prefix" and "domain."
  - Adjusted the start time for nightly cron jobs.
  - Made various minor fixes and UI improvements.

= 4.0.0.0 =

* **Release Date:** April 11, 2024
* **New Features and Improvements:**
  - Added support for cookie-less tracking.
  - Implemented the following specification changes:
    - Default PV limit has been set to 1,000PV.
    - Default targets for heatmaps and replay views are now set to all pages.
    - Looker Studio Connector is now accessible for free version users.
* **Other Changes:**
  - Made various minor fixes and improvements.
