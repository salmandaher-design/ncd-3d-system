<?php
/**
 * Optional WhatsApp sending through the RapidAPI "free-whatsapp-sender".
 *
 *   POST https://<host>/send
 *   { "message": "...", "receiver": "9639...", "id": "...", "token": "..." }
 *
 * `id` and `token` come from the provider's pairing step (GET /qrcode, then
 * scan the QR with the WhatsApp account that will do the sending).
 *
 * Everything here is optional: when it is not configured, or the provider is
 * down, the app keeps working and the admin uses the click-to-chat links.
 */
class WhatsAppApi
{
    /** Are all the pieces present to attempt an API send? */
    public static function isConfigured(): bool
    {
        return defined('WA_API_ENABLED') && WA_API_ENABLED
            && defined('WA_API_KEY')   && WA_API_KEY   !== ''
            && defined('WA_API_ID')    && WA_API_ID    !== ''
            && defined('WA_API_TOKEN') && WA_API_TOKEN !== ''
            && function_exists('curl_init');
    }

    /** Human-readable reason the API cannot be used (for the UI). */
    public static function unavailableReason(): string
    {
        if (!function_exists('curl_init')) {
            return 'PHP cURL is not available on this server.';
        }
        if (!defined('WA_API_ENABLED') || !WA_API_ENABLED) {
            return 'Automatic sending is off. Copy config/secrets.sample.php to config/secrets.php and set WA_API_ENABLED to true.';
        }
        if (!defined('WA_API_KEY') || WA_API_KEY === '') {
            return 'WA_API_KEY is missing in config/secrets.php.';
        }
        if (!defined('WA_API_ID') || WA_API_ID === '' || !defined('WA_API_TOKEN') || WA_API_TOKEN === '') {
            return 'WA_API_ID / WA_API_TOKEN are missing — pair a WhatsApp account first (the provider\'s /qrcode step).';
        }
        return '';
    }

    /**
     * Send one message.
     * @return array{ok:bool,error:string,http:int}
     */
    public static function send(string $number, string $message): array
    {
        $number = wa_number($number);           // normalise to 963XXXXXXXXX
        if ($number === '') {
            return ['ok' => false, 'error' => 'No WhatsApp number on file.', 'http' => 0];
        }
        if (!self::isConfigured()) {
            return ['ok' => false, 'error' => self::unavailableReason(), 'http' => 0];
        }

        $host = defined('WA_API_HOST') && WA_API_HOST !== ''
            ? WA_API_HOST : 'free-whatsapp-sender.p.rapidapi.com';

        $payload = json_encode([
            'message'  => $message,
            'receiver' => $number,
            'id'       => WA_API_ID,
            'token'    => WA_API_TOKEN,
        ], JSON_UNESCAPED_UNICODE);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://' . $host . '/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 20,   // keep a dead provider from hanging the page
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-rapidapi-host: ' . $host,
                'x-rapidapi-key: ' . WA_API_KEY,
            ],
        ]);

        $response = curl_exec($curl);
        $err      = curl_error($curl);
        $code     = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err !== '') {
            return ['ok' => false, 'error' => 'Network error: ' . $err, 'http' => $code];
        }
        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'error' => self::describe($code, (string) $response), 'http' => $code];
        }

        // A 2xx that still reports failure in the body counts as a failure.
        $data = json_decode((string) $response, true);
        if (is_array($data)) {
            foreach (['status', 'success', 'sent'] as $k) {
                if (array_key_exists($k, $data)) {
                    $v = $data[$k];
                    $good = ($v === true || $v === 1 || $v === '1'
                        || (is_string($v) && in_array(strtolower($v), ['true', 'ok', 'success', 'sent'], true)));
                    if (!$good) {
                        return ['ok' => false, 'error' => 'Provider rejected the message: ' . substr((string) $response, 0, 160), 'http' => $code];
                    }
                }
            }
        }
        return ['ok' => true, 'error' => '', 'http' => $code];
    }

    /** Turn an HTTP status into something an administrator can act on. */
    private static function describe(int $code, string $body): string
    {
        $snippet = trim(substr($body, 0, 200));
        switch (true) {
            case $code === 401 || $code === 403:
                return 'The API rejected the key (HTTP ' . $code . '). Check WA_API_KEY and your RapidAPI subscription.';
            case $code === 429:
                return 'Rate limit / quota reached on the API plan (HTTP 429).';
            case $code === 502 || $code === 503 || $code === 504:
                return 'The provider\'s server is down (HTTP ' . $code . '). Nothing was sent — use the "Send" links instead.';
            default:
                return 'API error HTTP ' . $code . ($snippet !== '' ? ': ' . $snippet : '');
        }
    }
}
