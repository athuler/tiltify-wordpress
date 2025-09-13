<?php
/**
 * WordPress widget for Tiltify WordPress
 * Provides a configurable sidebar widget for displaying fundraising data
 *
 * @package TiltifyWordPress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tiltify Fundraising Widget
 */
class Tiltify_Widget extends WP_Widget {

    /**
     * API instance
     */
    private $api;

    /**
     * Constructor
     */
    public function __construct($api = null) {
        $this->api = $api ?: new Tiltify_API();

        parent::__construct(
            'tiltify_widget',
            __('Tiltify Fundraising', TILTIFY_INTEGRATION_TEXT_DOMAIN),
            array(
                'description' => __('Display live fundraising data from your Tiltify campaign', TILTIFY_INTEGRATION_TEXT_DOMAIN),
                'classname' => 'tiltify-widget'
            )
        );
    }

    /**
     * Front-end display of widget
     */
    public function widget($args, $instance) {
        $campaign_id = !empty($instance['campaign_id']) ? $instance['campaign_id'] : get_option('tiltify_campaign_id', '');
        
        if (empty($campaign_id)) {
            if (current_user_can('manage_options')) {
                echo $args['before_widget'];
                echo '<div class="tiltify-error">' . __('Please configure your campaign ID in the widget settings.', TILTIFY_INTEGRATION_TEXT_DOMAIN) . '</div>';
                echo $args['after_widget'];
            }
            return;
        }

        $campaign_data = $this->api->get_campaign_data($campaign_id);

        if (is_wp_error($campaign_data)) {
            if (current_user_can('manage_options')) {
                echo $args['before_widget'];
                echo '<div class="tiltify-error">' . esc_html($campaign_data->get_error_message()) . '</div>';
                echo $args['after_widget'];
            }
            return;
        }

        // Widget output
        echo $args['before_widget'];

        // Title
        $title = !empty($instance['title']) ? $instance['title'] : $campaign_data['name'];
        if (!empty($title)) {
            echo $args['before_title'] . apply_filters('widget_title', $title, $instance, $this->id_base) . $args['after_title'];
        }

        $live_update = isset($instance['live_update']) ? $instance['live_update'] : true;
        $widget_classes = 'tiltify-widget-content';
        if ($live_update && $this->should_enable_live_updates()) {
            $widget_classes .= ' tiltify-live-update';
        }

        ?>
        <div class="<?php echo esc_attr($widget_classes); ?>" data-campaign-id="<?php echo esc_attr($campaign_data['id']); ?>" data-type="widget">
            
            <?php if (!empty($instance['show_description']) && !empty($campaign_data['description'])): ?>
            <div class="tiltify-widget-description">
                <?php echo wp_kses_post(wp_trim_words($campaign_data['description'], 20)); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($instance['show_progress'])): ?>
            <div class="tiltify-widget-progress">
                <div class="tiltify-progress-amounts">
                    <div class="tiltify-raised-amount">
                        <strong><?php echo esc_html($campaign_data['amount_raised_formatted']); ?></strong>
                        <span class="tiltify-label"><?php _e('raised', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></span>
                    </div>
                    <?php if (!empty($instance['show_goal'])): ?>
                    <div class="tiltify-goal-amount">
                        <?php echo sprintf(__('of %s goal', TILTIFY_INTEGRATION_TEXT_DOMAIN), esc_html($campaign_data['goal_formatted'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($instance['show_progress_bar'])): ?>
                <div class="tiltify-widget-progress-bar">
                    <div class="tiltify-progress-bg">
                        <div class="tiltify-progress-fill" style="width: <?php echo esc_attr($campaign_data['percentage']); ?>%;"></div>
                    </div>
                    <div class="tiltify-progress-percentage"><?php echo esc_html($campaign_data['percentage']); ?>%</div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($instance['show_stats'])): ?>
            <div class="tiltify-widget-stats">
                <?php if ($campaign_data['total_donations'] > 0): ?>
                <div class="tiltify-stat">
                    <span class="tiltify-stat-value"><?php echo esc_html(number_format($campaign_data['total_donations'])); ?></span>
                    <span class="tiltify-stat-label"><?php _e('supporters', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($instance['show_recent_donations'])): ?>
            <div class="tiltify-widget-recent">
                <?php 
                $donations_data = $this->api->get_recent_donations($campaign_id, 3);
                if (!is_wp_error($donations_data) && !empty($donations_data['donations'])): 
                ?>
                <h4><?php _e('Recent Supporters', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></h4>
                <ul class="tiltify-recent-list">
                    <?php foreach (array_slice($donations_data['donations'], 0, 3) as $donation): ?>
                    <li class="tiltify-recent-item">
                        <span class="tiltify-donor"><?php echo esc_html($donation['donor_name']); ?></span>
                        <?php if (!empty($instance['show_amounts'])): ?>
                        <span class="tiltify-amount"><?php echo esc_html($donation['amount_formatted']); ?></span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($instance['show_donate_button'])): ?>
            <div class="tiltify-widget-donate">
                <?php 
                $button_text = !empty($instance['button_text']) ? $instance['button_text'] : __('Donate Now', TILTIFY_INTEGRATION_TEXT_DOMAIN);
                $donate_url = $this->api->get_donation_url($campaign_id);
                ?>
                <a href="<?php echo esc_url($donate_url); ?>" class="tiltify-donate-button tiltify-widget-button" target="_blank">
                    <?php echo esc_html($button_text); ?>
                </a>
            </div>
            <?php endif; ?>

            <?php if (!empty($instance['show_powered_by'])): ?>
            <div class="tiltify-widget-powered">
                <small><?php _e('Powered by', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?> <a href="https://tiltify.com" target="_blank" rel="noopener">Tiltify</a></small>
            </div>
            <?php endif; ?>
        </div>
        <?php

        echo $args['after_widget'];
    }

    /**
     * Back-end widget form
     */
    public function form($instance) {
        // Set default values
        $defaults = array(
            'title' => '',
            'campaign_id' => get_option('tiltify_campaign_id', ''),
            'show_description' => false,
            'show_progress' => true,
            'show_goal' => true,
            'show_progress_bar' => true,
            'show_stats' => true,
            'show_recent_donations' => true,
            'show_amounts' => true,
            'show_donate_button' => true,
            'button_text' => __('Donate Now', TILTIFY_INTEGRATION_TEXT_DOMAIN),
            'show_powered_by' => true,
            'live_update' => true
        );

        $instance = wp_parse_args((array) $instance, $defaults);
        ?>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>" placeholder="<?php _e('Leave blank to use campaign name', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?>" />
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('campaign_id')); ?>"><?php _e('Campaign ID:', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('campaign_id')); ?>" name="<?php echo esc_attr($this->get_field_name('campaign_id')); ?>" type="text" value="<?php echo esc_attr($instance['campaign_id']); ?>" placeholder="<?php _e('Leave blank to use default', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?>" />
            <small><?php _e('Override the default campaign ID from plugin settings', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></small>
        </p>

        <p><strong><?php _e('Display Options:', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></strong></p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked($instance['show_description']); ?> id="<?php echo esc_attr($this->get_field_id('show_description')); ?>" name="<?php echo esc_attr($this->get_field_name('show_description')); ?>" />
            <label for="<?php echo esc_attr($this->get_field_id('show_description')); ?>"><?php _e('Show campaign description', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></label>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked($instance['show_progress']); ?> id="<?php echo esc_attr($this->get_field_id('show_progress')); ?>" name="<?php echo esc_attr($this->get_field_name('show_progress')); ?>" />
            <label for="<?php echo esc_attr($this->get_field_id('show_progress')); ?>"><?php _e('Show progress/amounts', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></label>
        </p>

        <p style="margin-left: 20px;">
            <input class="checkbox" type="checkbox" <?php checked($instance['show_goal']); ?> id="<?php echo esc_attr($this->get_field_id('show_goal')); ?>" name="<?php echo esc_attr($this->get_field_name('show_goal')); ?>" />
            <label for="<?php echo esc_attr($this->get_field_id('show_goal')); ?>"><?php _e('Show goal amount', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></label>
        </p>

        <p style="margin-left: 20px;">
            <input class="checkbox" type="checkbox" <?php checked($instance['show_progress_bar']); ?> id="<?php echo esc_attr($this->get_field_id('show_progress_bar')); ?>" name="<?php echo esc_attr($this->get_field_name('show_progress_bar')); ?>" />
            <label for="<?php echo esc_attr($this->get_field_id('show_progress_bar')); ?>"><?php _e('Show progress bar', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></label>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked($instance['show_stats']); ?> id="<?php echo esc_attr($this->get_field_id('show_stats')); ?>" name="<?php echo esc_attr($this->get_field_name('show_stats')); ?>" />
            <label for="<?php echo esc_attr($this->get_field_id('show_stats')); ?>"><?php _e('Show supporter count', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></label>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked($instance['show_recent_donations']); ?> id="<?php echo esc_attr($this->get_field_id('show_recent_donations')); ?>" name="<?php echo esc_attr($this->get_field_name('show_recent_donations')); ?>" />
            <label for="<?php echo esc_attr($this->get_field_id('show_recent_donations')); ?>"><?php _e('Show recent supporters', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></label>
        </p>

        <p style="margin-left: 20px;">
            <input class="checkbox" type="checkbox" <?php checked($instance['show_amounts']); ?> id="<?php echo esc_attr($this->get_field_id('show_amounts')); ?>" name="<?php echo esc_attr($this->get_field_name('show_amounts')); ?>" />
            <label for="<?php echo esc_attr($this->get_field_id('show_amounts')); ?>"><?php _e('Show donation amounts', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></label>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked($instance['show_donate_button']); ?> id="<?php echo esc_attr($this->get_field_id('show_donate_button')); ?>" name="<?php echo esc_attr($this->get_field_name('show_donate_button')); ?>" />
            <label for="<?php echo esc_attr($this->get_field_id('show_donate_button')); ?>"><?php _e('Show donate button', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></label>
        </p>

        <p style="margin-left: 20px;">
            <label for="<?php echo esc_attr($this->get_field_id('button_text')); ?>"><?php _e('Button text:', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('button_text')); ?>" name="<?php echo esc_attr($this->get_field_name('button_text')); ?>" type="text" value="<?php echo esc_attr($instance['button_text']); ?>" />
        </p>

        <p><strong><?php _e('Advanced Options:', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></strong></p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked($instance['live_update']); ?> id="<?php echo esc_attr($this->get_field_id('live_update')); ?>" name="<?php echo esc_attr($this->get_field_name('live_update')); ?>" />
            <label for="<?php echo esc_attr($this->get_field_id('live_update')); ?>"><?php _e('Enable live updates', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></label>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked($instance['show_powered_by']); ?> id="<?php echo esc_attr($this->get_field_id('show_powered_by')); ?>" name="<?php echo esc_attr($this->get_field_name('show_powered_by')); ?>" />
            <label for="<?php echo esc_attr($this->get_field_id('show_powered_by')); ?>"><?php _e('Show "Powered by Tiltify"', TILTIFY_INTEGRATION_TEXT_DOMAIN); ?></label>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['campaign_id'] = (!empty($new_instance['campaign_id'])) ? sanitize_text_field($new_instance['campaign_id']) : '';
        $instance['button_text'] = (!empty($new_instance['button_text'])) ? sanitize_text_field($new_instance['button_text']) : __('Donate Now', TILTIFY_INTEGRATION_TEXT_DOMAIN);
        
        // Checkbox fields
        $checkboxes = array(
            'show_description',
            'show_progress',
            'show_goal',
            'show_progress_bar',
            'show_stats',
            'show_recent_donations',
            'show_amounts',
            'show_donate_button',
            'show_powered_by',
            'live_update'
        );

        foreach ($checkboxes as $checkbox) {
            $instance[$checkbox] = isset($new_instance[$checkbox]) ? true : false;
        }

        return $instance;
    }

    /**
     * Check if live updates should be enabled
     */
    private function should_enable_live_updates() {
        $refresh_interval = get_option('tiltify_refresh_interval', 30);
        return $refresh_interval > 0;
    }
}