<?php
// Copy this file to google_config.php and fill in your own Google OAuth client
// (Google Cloud Console > APIs & Services > Credentials > OAuth client ID).
define('GOOGLE_CLIENT_ID', 'your-client-id.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'your-client-secret');
// Must match an authorized redirect URI in your OAuth client settings.
define('GOOGLE_REDIRECT_URI', 'http://localhost/empMan/candidatePage/signin.php');
