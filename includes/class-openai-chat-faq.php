<?php
/**
 * FAQ Custom Post Type
 *
 * @package OpenAI_Chat
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles FAQ Custom Post Type
 */
class OpenAI_Chat_FAQ {
    /**
     * Initialize FAQ functionality
     */
    public function init(): void {
        // Register FAQ post type
        add_action('init', array($this, 'register_faq_post_type'));
        
        // Add FAQ meta box
        add_action('add_meta_boxes', array($this, 'add_faq_meta_box'));
        
        // Save FAQ meta data
        add_action('save_post', array($this, 'save_faq_meta_data'));
    }

    /**
     * Register FAQ post type
     */
    public function register_faq_post_type(): void {
        $labels = array(
            'name'                  => _x('FAQs', 'Post Type General Name', 'chatbot-nora'),
            'singular_name'         => _x('FAQ', 'Post Type Singular Name', 'chatbot-nora'),
            'menu_name'            => __('FAQs', 'chatbot-nora'),
            'name_admin_bar'       => __('FAQ', 'chatbot-nora'),
            'archives'             => __('FAQ Archives', 'chatbot-nora'),
            'attributes'           => __('FAQ Attributes', 'chatbot-nora'),
            'parent_item_colon'    => __('Parent FAQ:', 'chatbot-nora'),
            'all_items'            => __('All FAQs', 'chatbot-nora'),
            'add_new_item'         => __('Add New FAQ', 'chatbot-nora'),
            'add_new'              => __('Add New', 'chatbot-nora'),
            'new_item'             => __('New FAQ', 'chatbot-nora'),
            'edit_item'            => __('Edit FAQ', 'chatbot-nora'),
            'update_item'          => __('Update FAQ', 'chatbot-nora'),
            'view_item'            => __('View FAQ', 'chatbot-nora'),
            'view_items'           => __('View FAQs', 'chatbot-nora'),
            'search_items'         => __('Search FAQ', 'chatbot-nora'),
            'not_found'            => __('Not found', 'chatbot-nora'),
            'not_found_in_trash'   => __('Not found in Trash', 'chatbot-nora'),
        );

        $args = array(
            'label'               => __('FAQ', 'chatbot-nora'),
            'description'         => __('Frequently Asked Questions', 'chatbot-nora'),
            'labels'              => $labels,
            'supports'            => array('title', 'editor', 'custom-fields'),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => 'chatbot-nora',
            'menu_position'       => 6,
            'menu_icon'           => 'dashicons-editor-help',
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'show_in_rest'        => true,
            'rest_base'           => 'faqs',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );

        register_post_type('faq', $args);
    }

    /**
     * Add FAQ meta box
     */
    public function add_faq_meta_box(): void {
        add_meta_box(
            'faq_meta_box',
            __('FAQ Details', 'chatbot-nora'),
            array($this, 'render_faq_meta_box'),
            'faq',
            'normal',
            'high'
        );
    }

    /**
     * Render FAQ meta box
     */
    public function render_faq_meta_box($post): void {
        // Add nonce for security
        wp_nonce_field('faq_meta_box', 'faq_meta_box_nonce');

        // Get existing values
        $keywords = get_post_meta($post->ID, '_faq_keywords', true);
        $category = get_post_meta($post->ID, '_faq_category', true);

        // Output fields
        ?>
        <p>
            <label for="faq_keywords"><?php _e('Keywords (comma separated):', 'chatbot-nora'); ?></label>
            <input type="text" id="faq_keywords" name="faq_keywords" value="<?php echo esc_attr($keywords); ?>" class="widefat">
        </p>
        <p>
            <label for="faq_category"><?php _e('Category:', 'chatbot-nora'); ?></label>
            <input type="text" id="faq_category" name="faq_category" value="<?php echo esc_attr($category); ?>" class="widefat">
        </p>
        <?php
    }

    /**
     * Save FAQ meta data
     */
    public function save_faq_meta_data($post_id): void {
        // Check if nonce is set
        if (!isset($_POST['faq_meta_box_nonce'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['faq_meta_box_nonce'], 'faq_meta_box')) {
            return;
        }

        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save meta data
        if (isset($_POST['faq_keywords'])) {
            update_post_meta($post_id, '_faq_keywords', sanitize_text_field($_POST['faq_keywords']));
        }

        if (isset($_POST['faq_category'])) {
            update_post_meta($post_id, '_faq_category', sanitize_text_field($_POST['faq_category']));
        }
    }
} 