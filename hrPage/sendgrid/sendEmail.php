<?php
require_once 'config.php';

// Simple US endpoint test with clean API key
function simpleSendEmail() {
    $data = [
        'personalizations' => [[
            'to' => [[
                'email' => 'marcangelosoria335@gmail.com',
                'name' => 'Test User'
            ]]
        ]],
        'from' => [
            'email' => 'soria_marcangelo@plpasig.edu.ph',
            'name' => 'ACME CORP!'
        ],
        'subject' => 'Simple Test',
        'content' => [[
            'type' => 'text/plain',
            'value' => 'TESTING ULIT HEHE'
        ]]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.sendgrid.com/v3/mail/send',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . SENDGRID_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Status: $httpCode\n";
    echo "Response: $response\n";
    
    return $httpCode === 202;
}

simpleSendEmail();