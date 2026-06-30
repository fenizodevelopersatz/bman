<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('curl_get_with_token')) {
    function curl_get_with_token($url, $token) {
        $ch = curl_init();

        $headers = [
            "Authorization: $token"
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            return [
                'status' => false,
                'message' => $error,
                'http_code' => $http_code,
            ];
        }

        return [
            'status' => true,
            'data' => json_decode($response, true),
            'http_code' => $http_code,
        ];
    }
}
