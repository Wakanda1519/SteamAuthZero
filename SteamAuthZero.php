<?php

namespace Wakanda\SteamAuthZero;

if (!class_exists('Wakanda\SteamAuthZero\SteamUser')) {
    require_once __DIR__ . '/Models/SteamUser.php';
}

use Exception;
use RuntimeException;

class SteamAuthZero
{
    private const OPENID_URL = 'https://steamcommunity.com/openid/login';
    private const API_URL = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/';

    public function __construct(
        private string $returnUrl,
        private string $apiKey = ''
    ) {}

    public function getLoginUrl(): string
    {
        $parsed = parse_url($this->returnUrl);
        $realm = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? $_SERVER['HTTP_HOST']);

        $params = [
            'openid.ns'         => 'http://specs.openid.net/auth/2.0',
            'openid.mode'       => 'checkid_setup',
            'openid.return_to'  => $this->returnUrl,
            'openid.realm'      => $realm,
            'openid.identity'   => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ];

        return self::OPENID_URL . '?' . http_build_query($params);
    }

    public function validate(): ?string
    {
        $data = $_GET;
        
        if (!isset($data['openid_mode']) || $data['openid_mode'] !== 'id_res') {
            return null;
        }

        $params = [
            'openid.ns'           => 'http://specs.openid.net/auth/2.0',
            'openid.mode'         => 'check_authentication',
            'openid.assoc_handle' => $data['openid_assoc_handle'],
            'openid.signed'       => $data['openid_signed'],
            'openid.sig'          => $data['openid_sig'],
        ];

        foreach (explode(',', $data['openid_signed']) as $field) {
            $key = 'openid_' . str_replace('.', '_', $field);
            
            if (isset($data[$key])) {
                $params['openid.' . $field] = $data[$key];
            }
        }

        $response = $this->postRequest(self::OPENID_URL, $params);

        // --- БЛОК ОТЛАДКИ ---
        // Если Steam вернул не то, что мы ждем, выведем это на экран
        // if (!str_contains($response, 'is_valid:true')) {
        //     echo "<pre><strong>DEBUG STEAM RESPONSE:</strong><br>";
        //     echo "Ответ от Steam: " . htmlspecialchars($response) . "<br>";
        //     echo "Мы отправили параметры:<br>";
        //     print_r($params);
        //     echo "</pre>";
        //     die();
        // }
        // // ------------------------------------

        if (str_contains($response, 'is_valid:true')) {
            preg_match('#^https?://steamcommunity.com/openid/id/([0-9]{17,25})#', $data['openid_claimed_id'], $matches);
            return $matches[1] ?? null;
        }

        return null;
    }

    public function getUserInfo(string $steamId): SteamUser
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException("API Key is required for getUserInfo()");
        }

        $url = self::API_URL . '?' . http_build_query([
            'key' => $this->apiKey,
            'steamids' => $steamId
        ]);

        $context = stream_context_create([
            'http' => ['timeout' => 5, 'ignore_errors' => true],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false] // Для локалки
        ]);

        $json = @file_get_contents($url, false, $context);

        if ($json === false) {
            throw new RuntimeException("Failed to connect to Steam API");
        }

        $data = json_decode($json, true);
        
        if (!isset($data['response']['players'][0])) {
            throw new RuntimeException("No player data found");
        }

        return SteamUser::fromArray($data['response']['players'][0]);
    }

    private function postRequest(string $url, array $params): string
    {
        $content = http_build_query($params);

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n" .
                             "Content-Length: " . strlen($content) . "\r\n",
                'content' => $content,
                'timeout' => 10
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);

        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            $error = error_get_last();
            throw new RuntimeException($error['message'] ?? 'Connection failed');
        }

        return $result;
    }
}
