<?php
/**
 * Shortcodes handler for Tiltify WordPress
 * Manages all shortcode functionality
 *
 * @package TiltifyWordPress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles all shortcode functionality for the Tiltify plugin
 */
class Tiltify_Shortcodes {

    /**
     * API instance
     */
    private $api;

    /**
     * Constructor
     */
    public function __construct($api) {
        $this->api = $api;
        $this->init_shortcodes();
    }

    /**
     * Initialize all shortcodes
     */
    private function init_shortcodes() {
        add_shortcode('tiltify_amount', array($this, 'display_amount'));
        add_shortcode('tiltify_goal', array($this, 'display_goal'));
        add_shortcode('tiltify_progress', array($this, 'display_progress'));
        add_shortcode('tiltify_donate', array($this, 'display_donate_button'));
        add_shortcode('tiltify_campaign_info', array($this, 'display_campaign_info'));
        add_shortcode('tiltify_recent_donations', array($this, 'display_recent_donations'));
    }

    /**
     * Display current amount raised
     * Usage: [tiltify_amount campaign_id="123" class="my-class" style="color: red;"]
     */
    public function display_amount($atts) {
        $atts = shortcode_atts(array(
            'campaign_id' => '',
            'class' => '',
            'style' => '',
            'prefix' => '',
            'suffix' => '',
            'live_update' => 'true'
        ), $atts, 'tiltify_amount');

        $campaign_data = $this->api->get_campaign_data($atts['campaign_id']);

        if (is_wp_error($campaign_data)) {
            return $this->render_error($campaign_data->get_error_message());
        }

        $classes = 'tiltify-amount';
        if (!empty($atts['class'])) {
            $classes .= ' ' . sanitize_html_class($atts['class']);
        }
        if ($atts['live_update'] === 'true') {
            $classes .= ' tiltify-live-update';
        }

        $style_attr = !empty($atts['style']) ? ' style="' . esc_attr($atts['style']) . '"' : '';
        
        $content = $atts['prefix'] . $campaign_data['amount_raised_formatted'] . $atts['suffix'];

        return sprintf(
            '<span class="%s" data-campaign-id="%s" data-type="amount"%s>%s</span>',
            esc_attr($classes),
            esc_attr($campaign_data['id']),
            $style_attr,
            esc_html($content)
        );
    }

    /**
     * Display campaign goal
     * Usage: [tiltify_goal campaign_id="123" class="my-class"]
     */
    public function display_goal($atts) {
        $atts = shortcode_atts(array(
            'campaign_id' => '',
            'class' => '',
            'style' => '',
            'prefix' => '',
            'suffix' => ''
        ), $atts, 'tiltify_goal');

        $campaign_data = $this->api->get_campaign_data($atts['campaign_id']);

        if (is_wp_error($campaign_data)) {
            return $this->render_error($campaign_data->get_error_message());
        }

        $classes = 'tiltify-goal';
        if (!empty($atts['class'])) {
            $classes .= ' ' . sanitize_html_class($atts['class']);
        }

        $style_attr = !empty($atts['style']) ? ' style="' . esc_attr($atts['style']) . '"' : '';
        
        $content = $atts['prefix'] . $campaign_data['goal_formatted'] . $atts['suffix'];

        return sprintf(
            '<span class="%s" data-campaign-id="%s" data-type="goal"%s>%s</span>',
            esc_attr($classes),
            esc_attr($campaign_data['id']),
            $style_attr,
            esc_html($content)
        );
    }

    /**
     * Display progress bar
     * Usage: [tiltify_progress campaign_id="123" show_percentage="true" show_amounts="true"]
     */
    public function display_progress($atts) {
        $atts = shortcode_atts(array(
            'campaign_id' => '',
            'class' => '',
            'style' => '',
            'show_percentage' => 'true',
            'show_amounts' => 'true',
            'show_goal' => 'true',
            'height' => '20px',
            'color' => '#00a651',
            'background_color' => '#f0f0f0',
            'live_update' => 'true'
        ), $atts, 'tiltify_progress');

        $campaign_data = $this->api->get_campaign_data($atts['campaign_id']);

        if (is_wp_error($campaign_data)) {
            return $this->render_error($campaign_data->get_error_message());
        }

        $classes = 'tiltify-progress-container';
        if (!empty($atts['class'])) {
            $classes .= ' ' . sanitize_html_class($atts['class']);
        }
        if ($atts['live_update'] === 'true') {
            $classes .= ' tiltify-live-update';
        }

        $style_attr = !empty($atts['style']) ? ' style="' . esc_attr($atts['style']) . '"' : '';
        
        $percentage = $campaign_data['percentage'];
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($classes); ?>" data-campaign-id="<?php echo esc_attr($campaign_data['id']); ?>" data-type="progress"<?php echo $style_attr; ?>>
            <?php if ($atts['show_amounts'] === 'true'): ?>
            <div class="tiltify-progress-amounts">
                <span class="tiltify-raised"><?php echo esc_html($campaign_data['amount_raised_formatted']); ?></span>
                <?php if ($atts['show_goal'] === 'true'): ?>
                <span class="tiltify-goal-text"><?php echo sprintf(__('of %s', TILTIFY_INTEGRATION_TEXT_DOMAIN), esc_html($campaign_data['goal_formatted'])); ?></span>
                <?php endif; ?>
                <?php if ($atts['show_percentage'] === 'true'): ?>
                <span class="tiltify-percentage">(<?php echo esc_html($percentage); ?>%)</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="tiltify-progress-bar" style="height: <?php echo esc_attr($atts['height']); ?>; background-color: <?php echo esc_attr($atts['background_color']); ?>;">
                <div class="tiltify-progress-fill" style="width: <?php echo esc_attr($percentage); ?>%; background-color: <?php echo esc_attr($atts['color']); ?>; height: 100%;"></div>
            </div>
            
            <?php if ($atts['show_percentage'] === 'true' && $atts['show_amounts'] === 'false'): ?>
            <div class="tiltify-progress-percentage">
                <?php echo esc_html($percentage); ?>%
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Display donate button
     * Usage: [tiltify_donate campaign_id="123" text="Donate Now" class="btn-primary"]
     */
    public function display_donate_button($atts) {
        $atts = shortcode_atts(array(
            'campaign_id' => '',
            'text' => __('Donate Now', TILTIFY_INTEGRATION_TEXT_DOMAIN),
            'class' => '',
            'style' => '',
            'target' => '_blank',
            'size' => 'medium'
        ), $atts, 'tiltify_donate');

        $campaign_id = !empty($atts['campaign_id']) ? $atts['campaign_id'] : get_option('tiltify_campaign_id', '');
        
        if (empty($campaign_id)) {
            return $this->render_error(__('Campaign ID is required', TILTIFY_INTEGRATION_TEXT_DOMAIN));
        }

        $donate_url = $this->api->get_donation_url($campaign_id);
        
        if (empty($donate_url)) {
            return $this->render_error(__('Could not generate donation URL', TILTIFY_INTEGRATION_TEXT_DOMAIN));
        }

        $classes = 'tiltify-donate-button tiltify-button-' . sanitize_html_class($atts['size']);
        if (!empty($atts['class'])) {
            $classes .= ' ' . sanitize_html_class($atts['class']);
        }

        $style_attr = !empty($atts['style']) ? ' style="' . esc_attr($atts['style']) . '"' : '';

        return sprintf(
            '<a href="%s" class="%s" target="%s"%s>%s</a>',
            esc_url($donate_url),
            esc_attr($classes),
            esc_attr($atts['target']),
            $style_attr,
            esc_html($atts['text'])
        );
    }

    /**
     * Display comprehensive campaign information
     * Usage: [tiltify_campaign_info campaign_id="123" show="name,description,progress"]
     */
    public function display_campaign_info($atts) {
        $atts = shortcode_atts(array(
            'campaign_id' => '',
            'class' => '',
            'style' => '',
            'show' => 'name,progress,donate',
            'live_update' => 'true'
        ), $atts, 'tiltify_campaign_info');

        $campaign_data = $this->api->get_campaign_data($atts['campaign_id']);

        if (is_wp_error($campaign_data)) {
            return $this->render_error($campaign_data->get_error_message());
        }

        $show_items = array_map('trim', explode(',', $atts['show']));
        
        $classes = 'tiltify-campaign-info';
        if (!empty($atts['class'])) {
            $classes .= ' ' . sanitize_html_class($atts['class']);
        }
        if ($atts['live_update'] === 'true') {
            $classes .= ' tiltify-live-update';
        }

        $style_attr = !empty($atts['style']) ? ' style="' . esc_attr($atts['style']) . '"' : '';

        ob_start();
        ?>
        <div class="<?php echo esc_attr($classes); ?>" data-campaign-id="<?php echo esc_attr($campaign_data['id']); ?>" data-type="campaign_info"<?php echo $style_attr; ?>>
            <?php if (in_array('name', $show_items) && !empty($campaign_data['name'])): ?>
            <h3 class="tiltify-campaign-name"><?php echo esc_html($campaign_data['name']); ?></h3>
            <?php endif; ?>

            <?php if (in_array('description', $show_items) && !empty($campaign_data['description'])): ?>
            <div class="tiltify-campaign-description"><?php echo wp_kses_post($campaign_data['description']); ?></div>
            <?php endif; ?>

            <?php if (in_array('progress', $show_items)): ?>
            <div class="tiltify-campaign-progress">
                <?php echo $this->display_progress(array('campaign_id' => $campaign_data['id'], 'live_update' => 'false')); ?>
            </div>
            <?php endif; ?>

            <?php if (in_array('donate', $show_items)): ?>
            <div class="tiltify-campaign-donate">
                <?php echo $this->display_donate_button(array('campaign_id' => $campaign_data['id'])); ?>
            </div>
            <?php endif; ?>

            <?php if (in_array('stats', $show_items)): ?>
            <div class="tiltify-campaign-stats">
                <span class="tiltify-stat">
                    <strong><?php _e('Donations:', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></strong>
                    <?php echo esc_html($campaign_data['total_donations']); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Display recent donations
     * Usage: [tiltify_recent_donations campaign_id="123" limit="5" show_amounts="true"]
     */
    public function display_recent_donations($atts) {
        $atts = shortcode_atts(array(
            'campaign_id' => '',
            'limit' => '5',
            'class' => '',
            'style' => '',
            'show_amounts' => 'true',
            'show_comments' => 'true',
            'live_update' => 'true',
            'anonymous_text' => __('Anonymous', TILTIFY_INTEGRATION_TEXT_DOMAIN)
        ), $atts, 'tiltify_recent_donations');

        $donations_data = $this->api->get_recent_donations($atts['campaign_id'], intval($atts['limit']));

        if (is_wp_error($donations_data)) {
            return $this->render_error($donations_data->get_error_message());
        }

        if (empty($donations_data['donations'])) {
            return '<div class="tiltify-no-donations">' . __('No donations yet.', TILTIFY_INTEGRATION_TEXT_DOMAIN) . '</div>';
        }

        $classes = 'tiltify-recent-donations';
        if (!empty($atts['class'])) {
            $classes .= ' ' . sanitize_html_class($atts['class']);
        }
        if ($atts['live_update'] === 'true') {
            $classes .= ' tiltify-live-update';
        }

        $style_attr = !empty($atts['style']) ? ' style="' . esc_attr($atts['style']) . '"' : '';

        ob_start();
        ?>
        <div class="<?php echo esc_attr($classes); ?>" data-campaign-id="<?php echo esc_attr($atts['campaign_id']); ?>" data-type="recent_donations"<?php echo $style_attr; ?>>
            <ul class="tiltify-donations-list">
                <?php foreach ($donations_data['donations'] as $donation): ?>
                <li class="tiltify-donation-item">
                    <div class="tiltify-donation-header">
                        <span class="tiltify-donor-name">
                            <?php echo esc_html(!empty($donation['donor_name']) ? $donation['donor_name'] : $atts['anonymous_text']); ?>
                        </span>
                        <?php if ($atts['show_amounts'] === 'true'): ?>
                        <span class="tiltify-donation-amount"><?php echo esc_html($donation['amount_formatted']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($atts['show_comments'] === 'true' && !empty($donation['donor_comment'])): ?>
                    <div class="tiltify-donation-comment">
                        <?php echo esc_html($donation['donor_comment']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($donation['completed_at'])): ?>
                    <div class="tiltify-donation-time">
                        <?php echo esc_html(human_time_diff(strtotime($donation['completed_at']), current_time('timestamp')) . ' ago'); ?>
                    </div>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render error message
     */
    private function render_error($message) {
        if (current_user_can('manage_options')) {
            return '<div class="tiltify-error">' . esc_html($message) . '</div>';
        }
        return '<div class="tiltify-error">' . __('Unable to load fundraising data.', TILTIFY_INTEGRATION_TEXT_DOMAIN) . '</div>';
    }

    /**
     * Get common shortcode attributes with defaults
     */
    private function get_common_atts($atts, $tag) {
        return shortcode_atts(array(
            'campaign_id' => '',
            'class' => '',
            'style' => ''
        ), $atts, $tag);
    }
}