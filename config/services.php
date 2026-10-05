<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // ── OTP delivery gateways (set in .env to go live) ──────────────────────
    // Leave blank locally: OTP falls back to the log channel for development.
    'sms' => [
        'endpoint' => env('SMS_API_URL'),       // e.g. https://api.gateway.com/send
        'key'      => env('SMS_API_KEY'),
        'sender'   => env('SMS_SENDER_ID'),
    ],

    'whatsapp' => [
        'endpoint' => env('WHATSAPP_API_URL'),  // e.g. WhatsApp Cloud API messages URL
        'token'    => env('WHATSAPP_API_TOKEN'),
        'from'     => env('WHATSAPP_FROM'),
    ],

    // ── Social login (Continue with Google / Facebook) ─────────────────────
    // Buttons appear only when both id + secret are set AND the admin toggle is on.
    // Callback URLs to register with Google / Meta: {APP_URL}/auth/google/callback
    // and {APP_URL}/auth/facebook/callback.
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'facebook' => [
        'client_id'     => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect'      => env('FACEBOOK_REDIRECT_URI', '/auth/facebook/callback'),
    ],

    // ── Meta Conversions API (token + switches live in admin → Marketing) ──
    'meta' => [
        'graph_version' => env('META_GRAPH_VERSION', 'v23.0'),
    ],

];
