<?php
// backend/SmsService.php

require_once __DIR__ . '/../config.php';

class SmsService {

    private static $logTableReady = false;
    private static $logSchemaMode = null;
    
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
            self::logDispatch(0, 0, 0, 0, 'API key not configured', $message);
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
            self::logDispatch(0, 0, 0, 0, 'No valid phone numbers', $message);
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
            self::logDispatch(count($formattedNumbers), 0, count($formattedNumbers), $httpCode, 'cURL Error: ' . $error, $message);
            return ['status' => false, 'error' => 'cURL Error: ' . $error];
        }

        $result = json_decode($response, true);

        if (!is_array($result)) {
            self::logDispatch(count($formattedNumbers), 0, count($formattedNumbers), $httpCode, 'Invalid JSON response from SMS provider', $message);
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
        self::logDispatch(count($formattedNumbers), $successCount, $failedCount, $httpCode, $providerMessage, $message);

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

    private static function ensureLogTable($conn) {
        if (self::$logTableReady) {
            return;
        }

        $conn->query("CREATE TABLE IF NOT EXISTS sms_dispatch_log (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            recipient_count INT NOT NULL DEFAULT 0,
            success_count INT NOT NULL DEFAULT 0,
            failed_count INT NOT NULL DEFAULT 0,
            provider_http_code INT NOT NULL DEFAULT 0,
            provider_message VARCHAR(255) NOT NULL DEFAULT '',
            message_preview VARCHAR(255) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_sms_dispatch_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::$logTableReady = true;
    }

    private static function resolveLogSchemaMode($conn) {
        if (self::$logSchemaMode !== null) {
            return self::$logSchemaMode;
        }

        $hasLegacy = false;
        $hasNew = false;

        $legacyCheck = $conn->query("SHOW COLUMNS FROM sms_dispatch_log LIKE 'recipient'");
        if ($legacyCheck && $legacyCheck->num_rows > 0) {
            $hasLegacy = true;
        }

        $newCheck = $conn->query("SHOW COLUMNS FROM sms_dispatch_log LIKE 'recipient_count'");
        if ($newCheck && $newCheck->num_rows > 0) {
            $hasNew = true;
        }

        if ($hasNew) {
            self::$logSchemaMode = 'new';
        } elseif ($hasLegacy) {
            self::$logSchemaMode = 'legacy';
        } else {
            self::$logSchemaMode = 'none';
        }

        return self::$logSchemaMode;
    }

    private static function logDispatch($recipientCount, $successCount, $failedCount, $httpCode, $providerMessage, $message) {
        if (!function_exists('getDbConnection')) {
            return;
        }

        $conn = null;
        try {
            $conn = @getDbConnection();
            if (!$conn || $conn->connect_error) {
                return;
            }

            self::ensureLogTable($conn);

            $recipientCount = (int) $recipientCount;
            $successCount = (int) $successCount;
            $failedCount = (int) $failedCount;
            $httpCode = (int) $httpCode;
            $providerMessage = trim((string) $providerMessage);
            $messagePreview = substr(trim((string) $message), 0, 255);

            $schemaMode = self::resolveLogSchemaMode($conn);

            if ($schemaMode === 'new') {
                $sql = 'INSERT INTO sms_dispatch_log (recipient_count, success_count, failed_count, provider_http_code, provider_message, message_preview) VALUES (?, ?, ?, ?, ?, ?)';
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('iiiiss', $recipientCount, $successCount, $failedCount, $httpCode, $providerMessage, $messagePreview);
                    $stmt->execute();
                    $stmt->close();
                }
            } elseif ($schemaMode === 'legacy') {
                $recipientLabel = ($recipientCount > 1)
                    ? ('multiple (' . $recipientCount . ')')
                    : 'single';

                if ($successCount > 0 && $failedCount === 0) {
                    $providerStatus = 'sent';
                } elseif ($successCount > 0 && $failedCount > 0) {
                    $providerStatus = 'partial';
                } else {
                    $providerStatus = 'failed';
                }

                $messageType = 'general';
                if (stripos($messagePreview, 'Matched Scholarships') !== false) {
                    $messageType = 'matched_scholarships';
                } elseif (stripos($messagePreview, 'Deadline Reminder') !== false) {
                    $messageType = 'deadline_reminder';
                } elseif (stripos($messagePreview, 'Profile Completion') !== false) {
                    $messageType = 'profile_completion';
                }

                $triggerSource = 'manual';

                $legacySql = 'INSERT INTO sms_dispatch_log (recipient, message_preview, message_type, trigger_source, provider_http_code, provider_status, provider_message) VALUES (?, ?, ?, ?, ?, ?, ?)';
                $legacyStmt = $conn->prepare($legacySql);
                if ($legacyStmt) {
                    $legacyStmt->bind_param('ssssiss', $recipientLabel, $messagePreview, $messageType, $triggerSource, $httpCode, $providerStatus, $providerMessage);
                    $legacyStmt->execute();
                    $legacyStmt->close();
                }
            }
        } catch (Throwable $e) {
            error_log('SmsService logDispatch skipped: ' . $e->getMessage());
        } finally {
            if ($conn) {
                $conn->close();
            }
        }
    }
}
?>
