<?php
/**
 * Frontend Assets
 *
 * @package OpenAI_Chat
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles frontend assets
 */
class OpenAI_Chat_Assets {
    /**
     * Initialize assets
     */
    public function init(): void {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets(): void {
        // Only load assets if chat is enabled and API key is configured
        if (!get_option('openai_chat_enabled', true) || empty(get_option('openai_chat_api_key'))) {
            return;
        }

        // Enqueue Dashicons
        wp_enqueue_style('dashicons');

        // Enqueue styles
        wp_enqueue_style(
            'openai-chat',
            plugins_url('assets/css/openai-chat.css', dirname(dirname(__FILE__))),
            array(),
            OPENAI_CHAT_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script(
            'openai-chat',
            plugins_url('assets/js/openai-chat.js', dirname(dirname(__FILE__))),
            array('jquery'),
            OPENAI_CHAT_VERSION,
            true
        );

        // Localize script
        $this->localize_script();
    }

    public function localize_script() {
        wp_localize_script('openai-chat', 'openaiChat', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('openai_chat_nonce'),
            'i18n' => array(
                'thinking' => __('Tenker...', 'chatbot-nora'),
                'error' => __('Beklager, det oppstod en feil. Vennligst prøv igjen.', 'chatbot-nora'),
                'endChat' => __('Avslutt', 'chatbot-nora'),
                'confirmEndChat' => __('Er du sikker på at du vil avslutte chatten?', 'chatbot-nora'),
                'welcomeTitle' => __('Velkommen til chatten!', 'chatbot-nora'),
                'welcomeMessage' => __('Før vi begynner, trenger vi litt informasjon fra deg.', 'chatbot-nora'),
                'nameLabel' => __('Ditt navn', 'chatbot-nora'),
                'emailLabel' => __('Din e-postadresse', 'chatbot-nora'),
                'startChat' => __('Start chat', 'chatbot-nora'),
                'cancel' => __('Avbryt', 'chatbot-nora'),
                'nameRequired' => __('Vennligst skriv inn ditt navn', 'chatbot-nora'),
                'invalidEmail' => __('Vennligst skriv inn en gyldig e-postadresse', 'chatbot-nora'),
                'initialMessage' => __('Hei %s! Hvordan kan jeg hjelpe deg i dag?', 'chatbot-nora')
            )
        ));
    }
} 