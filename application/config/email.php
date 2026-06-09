<?php defined('BASEPATH') or exit('No direct script access allowed');

// Add custom values by settings them to the $config array.
// Example: $config['smtp_host'] = 'smtp.gmail.com';
// @link https://codeigniter.com/user_guide/libraries/email.html

$config['useragent'] = 'AgendaRRF';
$config['protocol'] = 'smtp';
$config['mailtype'] = 'html';
$config['smtp_debug'] = '0';
$config['smtp_host'] = getenv('SMTP_HOST') ?: 'localhost';
$config['smtp_user'] = getenv('SMTP_USER') ?: '';
$config['smtp_pass'] = getenv('SMTP_PASS') ?: '';
$config['smtp_port'] = getenv('SMTP_PORT') ?: 1025;
$config['smtp_crypto'] = getenv('SMTP_CRYPTO') ?: 'tls';
$config['smtp_auth'] = filter_var(getenv('SMTP_AUTH') ?: 'true', FILTER_VALIDATE_BOOLEAN);
$config['from_name'] = getenv('FROM_NAME') ?: 'AgendaRRF';
$config['from_address'] = getenv('FROM_ADDRESS') ?: 'no-reply@example.com';
$config['reply_to'] = getenv('REPLY_TO') ?: $config['from_address'];
$config['crlf'] = "\r\n";
$config['newline'] = "\r\n";
