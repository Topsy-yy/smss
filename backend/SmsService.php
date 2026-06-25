<?php
// backend/SmsService.php

require_once __DIR__ . '/../config.php';

class SmsService {
    
    /**
     * Send an SMS using Africa's Talking REST API
     * 
     * @param array|string $phoneNumbers A single phone number or array of phone numbers.
     * @param string $message The text message to send.
     * @return array Array containing success status and AT API response.
     */
    public static function sendSms($phoneNumbers, $message) {
        $username = trim((string) AT_USERNAME);
        $apiKey = trim((string) AT_API_KEY);
        $senderId = trim((string) AT_SENDER_ID);
        
        if (empty($apiKey)) {
            return ['status' => false, 'error' => 'API Key not configured'];
        }

        // Format phone numbers
        if (!is_array($phoneNumbers)) {
            $phoneNumbers = [$phoneNumbers];
        }
        
        $formattedNumbers = [];
        foreach ($phoneNumbers as $phone) {
            $formatted = self::formatPhoneNumber($phone);
            if ($formatted) {
                $formattedNumbers[] = $formatted;
            }
        }
        
        if (empty($formattedNumbers)) {
            return ['status' => false, 'error' => 'No valid phone numbers provided'];
        }

        $url = ($username === 'sandbox') 
            ? 'https://api.sandbox.africastalking.com/version1/messaging' 
            : 'https://api.africastalking.com/version1/messaging';

        $postData = [
            'username' => $username,
            'to' => implode(',', $formattedNumbers),
            'message' => $message
        ];
        
        if (!empty($senderId)) {
            $postData['from'] = $senderId;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
            'apiKey: ' . $apiKey
        ]);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            return ['status' => false, 'error' => 'cURL Error: ' . $error];
        }

        $result = json_decode($response, true);

        if (!is_array($result)) {
            return [
                'status' => false,
                'error' => 'Invalid JSON response from SMS provider',
                'httpCode' => $httpCode,
                'rawResponse' => $response
            ];
        }

        $providerMessage = (string) ($result['SMSMessageData']['Message'] ?? '');
        $recipients = $result['SMSMessageData']['Recipients'] ?? [];
        $successCount = 0;
        $failedCount = 0;

        if (is_array($recipients)) {
            foreach ($recipients as $recipient) {
                $statusCode = (int) ($recipient['statusCode'] ?? 0);
                $statusText = strtolower((string) ($recipient['status'] ?? ''));
                $isSuccess = ($statusCode === 101) || ($statusText === 'success');
                if ($isSuccess) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            }
        }

        $isProviderError = ($httpCode >= 400);
        $hasDeliverySuccess = ($successCount > 0);

        // Record delivery details in PHP error logs for troubleshooting.
        error_log('SmsService sendSms result: HTTP=' . $httpCode . ' success=' . $successCount . ' failed=' . $failedCount . ' message=' . $providerMessage);

        return [
            'status' => (!$isProviderError && $hasDeliverySuccess),
            'httpCode' => $httpCode,
            'providerMessage' => $providerMessage,
            'successCount' => $successCount,
            'failedCount' => $failedCount,
            'response' => $result
        ];
    }

    /**
     * Helper to format local numbers to E.164 (Assuming +254 for default)
     */
    public static function formatPhoneNumber($phone) {
        $phone = preg_replace('/[^0-9+]/', '', (string)$phone);
        if (empty($phone)) return null;

        if (strpos($phone, '+') === 0) {
            return $phone; // Already formatted
        }
        
        if (strpos($phone, '0') === 0 && strlen($phone) == 10) {
            return '+254' . substr($phone, 1);
        }
        
        if (strpos($phone, '254') === 0 && strlen($phone) == 12) {
            return '+' . $phone;
        }

        return '+' . $phone;
    }
}
?>
