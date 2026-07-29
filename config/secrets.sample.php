<?php
/**
 * Secrets — TEMPLATE
 * ------------------
 * SETUP: copy this file to `config/secrets.php` and fill in your keys.
 *
 *   cp config/secrets.sample.php config/secrets.php
 *
 * `config/secrets.php` is gitignored, so real keys are never committed.
 * The app works fine without it — WhatsApp simply falls back to the
 * "open WhatsApp with the message ready" links.
 */

// ----- WhatsApp sending API (RapidAPI: free-whatsapp-sender) -----
// Turn on only after you have paired a WhatsApp account and have an id + token.
define('WA_API_ENABLED', false);

// Your RapidAPI key.
define('WA_API_KEY', '');

// RapidAPI host for the provider.
define('WA_API_HOST', 'free-whatsapp-sender.p.rapidapi.com');

// Session credentials returned by the provider's pairing callback
// (GET /qrcode -> scan the QR with the sending WhatsApp account).
// These usually expire when the phone unlinks the session.
define('WA_API_ID', '');
define('WA_API_TOKEN', '');
