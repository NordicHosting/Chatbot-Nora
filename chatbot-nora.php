<?php
/**
 * Plugin Name: Chatbot Nora
 * Plugin URI: https://nordichosting.no/chatbot-nora
 * Description: En intelligent chatbot basert på OpenAI's GPT-teknologi som kan hjelpe besøkende på din WordPress-nettside.
 * Version: 1.1.2
 * Author: Nordic Hosting
 * Author URI: https://nordichosting.no
 * Text Domain: chatbot-nora
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin version
define('OPENAI_CHAT_VERSION', '1.1.2');

// ... existing code ... 