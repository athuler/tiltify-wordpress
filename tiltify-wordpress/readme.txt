=== Tiltify WordPress ===
Tags: fundraising, charity, donations, tiltify, live updates
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display live fundraising data from your Tiltify campaigns with real-time updates, donation buttons, and customizable widgets.

== Description ==

Tiltify WordPress seamlessly connects your WordPress website with your Tiltify fundraising campaigns, allowing you to display live fundraising data, progress bars, donation buttons, and recent supporter information directly on your site.

**Key Features:**

* **Live Data Updates** - Real-time updates of fundraising amounts and progress without page refresh
* **Multiple Display Options** - Shortcodes, widgets, and customizable display formats
* **Responsive Design** - Works perfectly on desktop, tablet, and mobile devices
* **Easy Setup** - Simple configuration through WordPress admin panel
* **Secure API Integration** - Uses Tiltify API v5 with proper authentication
* **Performance Optimized** - Smart caching system to minimize API calls
* **Customizable Styling** - Full CSS control and multiple display options

**Shortcodes Available:**

* `[tiltify_amount]` - Display current amount raised
* `[tiltify_goal]` - Display campaign goal
* `[tiltify_progress]` - Show progress bar with percentage
* `[tiltify_donate]` - Add donation button
* `[tiltify_campaign_info]` - Complete campaign display
* `[tiltify_recent_donations]` - Show recent supporters

**Widget Support:**

Add a customizable Tiltify fundraising widget to your sidebars with options to show/hide different elements like progress bars, recent donations, supporter counts, and donation buttons.

**Perfect for:**

* Charity organizations
* Streamers and content creators
* Non-profit organizations
* Fundraising events
* Community campaigns

**Technical Features:**

* Caching system with configurable duration
* AJAX-powered live updates
* Error handling and fallback displays
* WordPress coding standards compliant
* Translation ready
* Multisite compatible
* Clean uninstall process

== Installation ==

1. Upload the `tiltify-integration` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Tiltify to configure your campaign settings
4. Enter your Campaign ID (required) and optionally your API token
5. Test your connection and save settings
6. Start using shortcodes or add the widget to your site

**Quick Start:**

1. Find your Tiltify Campaign ID from your campaign URL (e.g., if your campaign URL is `https://tiltify.com/@username/my-campaign`, your ID is the part after the last `/`)
2. Enter this ID in Settings > Tiltify
3. Use `[tiltify_progress]` shortcode on any page or post to display your progress bar

== Frequently Asked Questions ==

= Do I need a Tiltify API token? =

An API token is optional for public campaigns. However, it's recommended for better reliability and access to additional features. You can generate one from your Tiltify dashboard.

= How often does the live data update? =

By default, live updates occur every 30 seconds. You can configure this interval in the plugin settings (minimum 10 seconds, maximum 300 seconds).

= Can I customize the appearance? =

Yes! The plugin includes comprehensive CSS classes for styling. You can also add custom classes and styles through shortcode parameters.

= Does it work with caching plugins? =

Yes, the plugin is designed to work with caching plugins. It uses AJAX for live updates which bypass page caching.

= Can I display multiple campaigns? =

Yes, you can override the default campaign ID in any shortcode using the `campaign_id` parameter.

= Is it mobile responsive? =

Absolutely! All displays are fully responsive and work perfectly on mobile devices.

= How is performance handled? =

The plugin uses smart caching to minimize API calls, pauses updates when the browser tab is not active, and includes error handling for when the API is unavailable.

== Usage Examples ==

**Basic Progress Bar:**
`[tiltify_progress]`

**Custom Styled Amount:**
`[tiltify_amount class="my-amount" style="font-size: 24px; color: green;"]`

**Donation Button with Custom Text:**
`[tiltify_donate text="Support Our Cause!" class="btn btn-primary"]`

**Complete Campaign Display:**
`[tiltify_campaign_info show="name,progress,donate,stats"]`

**Recent Donations (limit 3):**
`[tiltify_recent_donations limit="3" show_amounts="true"]`

**Override Campaign ID:**
`[tiltify_progress campaign_id="your-other-campaign-id"]`

== Shortcode Reference ==

= [tiltify_amount] =

Display the current amount raised for your campaign.

**Parameters:**
* `campaign_id` - Override default campaign ID
* `class` - Add custom CSS class
* `style` - Add inline CSS styles
* `prefix` - Text to display before amount
* `suffix` - Text to display after amount
* `live_update` - Enable/disable live updates (true/false)

= [tiltify_goal] =

Display the campaign goal amount.

**Parameters:**
* `campaign_id` - Override default campaign ID
* `class` - Add custom CSS class
* `style` - Add inline CSS styles
* `prefix` - Text to display before goal
* `suffix` - Text to display after goal

= [tiltify_progress] =

Display a progress bar with fundraising progress.

**Parameters:**
* `campaign_id` - Override default campaign ID
* `class` - Add custom CSS class
* `style` - Add inline CSS styles
* `show_percentage` - Show percentage (true/false)
* `show_amounts` - Show raised/goal amounts (true/false)
* `show_goal` - Show goal amount (true/false)
* `height` - Progress bar height (e.g., "20px")
* `color` - Progress bar color
* `background_color` - Progress bar background color
* `live_update` - Enable/disable live updates (true/false)

= [tiltify_donate] =

Display a donation button linking to your Tiltify campaign.

**Parameters:**
* `campaign_id` - Override default campaign ID
* `text` - Button text (default: "Donate Now")
* `class` - Add custom CSS class
* `style` - Add inline CSS styles
* `target` - Link target (_blank, _self, etc.)
* `size` - Button size (small, medium, large)

= [tiltify_campaign_info] =

Display comprehensive campaign information.

**Parameters:**
* `campaign_id` - Override default campaign ID
* `class` - Add custom CSS class
* `style` - Add inline CSS styles
* `show` - Comma-separated list of elements to show (name, description, progress, donate, stats)
* `live_update` - Enable/disable live updates (true/false)

= [tiltify_recent_donations] =

Display a list of recent donations.

**Parameters:**
* `campaign_id` - Override default campaign ID
* `limit` - Number of donations to show (default: 5)
* `class` - Add custom CSS class
* `style` - Add inline CSS styles
* `show_amounts` - Show donation amounts (true/false)
* `show_comments` - Show donation comments (true/false)
* `live_update` - Enable/disable live updates (true/false)
* `anonymous_text` - Text to show for anonymous donors

== Widget Configuration ==

The Tiltify Fundraising widget can be added to any sidebar and includes the following options:

* **Title** - Custom widget title
* **Campaign ID** - Override default campaign
* **Show Description** - Display campaign description
* **Show Progress** - Display fundraising progress
* **Show Goal** - Display goal amount
* **Show Progress Bar** - Visual progress bar
* **Show Stats** - Display supporter count
* **Show Recent Donations** - List recent supporters
* **Show Amounts** - Display donation amounts
* **Show Donate Button** - Include donation button
* **Button Text** - Custom button text
* **Live Updates** - Enable real-time updates
* **Show "Powered by Tiltify"** - Attribution link

== Styling and Customization ==

**CSS Classes:**

The plugin provides extensive CSS classes for customization:

**Progress Bar:**
* `.tiltify-progress-container`
* `.tiltify-progress-bar`
* `.tiltify-progress-fill`
* `.tiltify-progress-amounts`
* `.tiltify-raised`
* `.tiltify-goal-text`
* `.tiltify-percentage`

**Donation Button:**
* `.tiltify-donate-button`
* `.tiltify-button-small`
* `.tiltify-button-medium`
* `.tiltify-button-large`

**Campaign Info:**
* `.tiltify-campaign-info`
* `.tiltify-campaign-name`
* `.tiltify-campaign-description`
* `.tiltify-campaign-progress`
* `.tiltify-campaign-donate`
* `.tiltify-campaign-stats`

**Recent Donations:**
* `.tiltify-recent-donations`
* `.tiltify-donations-list`
* `.tiltify-donation-item`
* `.tiltify-donation-header`
* `.tiltify-donor-name`
* `.tiltify-donation-amount`
* `.tiltify-donation-comment`
* `.tiltify-donation-time`

**Widget:**
* `.tiltify-widget-content`
* `.tiltify-widget-progress`
* `.tiltify-widget-stats`
* `.tiltify-widget-recent`
* `.tiltify-widget-donate`
* `.tiltify-widget-powered`

**States:**
* `.tiltify-loading`
* `.tiltify-error`
* `.tiltify-live-update`

== Performance Optimization ==

**Caching System:**

The plugin includes a smart caching system that:
* Caches API responses for configurable duration (default 5 minutes)
* Groups requests by campaign ID to minimize API calls
* Automatically clears expired cache data
* Provides manual cache clearing option in admin

**Live Update Optimization:**

Live updates are optimized for performance:
* Updates pause when browser tab is inactive
* Configurable update intervals (10-300 seconds)
* Debounced resize handlers
* Error handling with exponential backoff

**Best Practices:**

1. **Set appropriate cache duration** - Balance between fresh data and API usage
2. **Configure reasonable update intervals** - Too frequent updates can impact performance
3. **Use specific campaign IDs** - Avoid unnecessary API calls for unused campaigns
4. **Test with caching plugins** - Ensure compatibility with your caching setup

== Troubleshooting ==

**Common Issues:**

**"Campaign not found" error:**
* Verify your Campaign ID is correct
* Check if the campaign is public or requires API token
* Test connection in plugin settings

**Live updates not working:**
* Check browser console for JavaScript errors
* Verify AJAX is not being blocked
* Test with different browsers

**Styling issues:**
* Check for CSS conflicts with theme
* Use browser developer tools to inspect elements
* Add more specific CSS selectors if needed

**Performance issues:**
* Reduce update frequency
* Increase cache duration
* Check for plugin conflicts

**Debug Mode:**

Enable WordPress debug mode to see detailed error messages:

In wp-config.php:
`define('WP_DEBUG', true);`
`define('WP_DEBUG_LOG', true);`
`define('WP_DEBUG_DISPLAY', false);`

== Screenshots ==

1. Plugin settings page with connection testing
2. Progress bar display with live updates
3. Fundraising widget in sidebar
4. Campaign information display
5. Recent donations list
6. Donation button examples

== Changelog ==

= 1.0.0 =
* Initial release
* Live fundraising data display
* Real-time AJAX updates
* Multiple shortcodes available
* Customizable widget
* Responsive design
* Admin settings panel
* API connection testing
* Cache management
* Error handling and fallbacks

== Upgrade Notice ==

= 1.0.0 =
Initial release of Tiltify WordPress plugin.

== Support ==

For support, feature requests, or bug reports:

* **GitHub Repository:** [Link to your repository]
* **WordPress.org Support:** [Plugin support forum]
* **Documentation:** Complete documentation in README.md file
* **API Documentation:** https://developers.tiltify.com/

== Privacy ==

This plugin connects to the Tiltify API to retrieve fundraising data. No personal data from your WordPress site is sent to Tiltify. The plugin only requests public campaign information and donation data that you have configured to display.

Data retrieved from Tiltify may include:
* Campaign amounts and goals
* Public donation amounts and donor names (if public)
* Campaign descriptions and metadata

All data is cached locally according to your cache settings and is automatically cleaned up when the plugin is uninstalled.

== Developers ==

**Hooks and Filters:**

The plugin provides several hooks for developers:

* `tiltify_before_api_request` - Modify API requests
* `tiltify_after_api_response` - Process API responses
* `tiltify_shortcode_output` - Modify shortcode output
* `tiltify_widget_display` - Customize widget display

**Custom CSS Classes:**

All elements include CSS classes for easy customization. See the Styling and Customization section for complete list.

**API Access:**

Get the API instance:
`$tiltify = tiltify_integration();`
`$api = $tiltify->get_api();`

**GitHub Repository:**

Contributions welcome! Visit our GitHub repository for the latest development version and to submit issues or pull requests.