<?php
/**
 * Adzuna API Client
 *
 * Thin wrapper around the Adzuna job search REST API.
 * Docs: https://developer.adzuna.com/overview
 *
 * - Reads credentials from .env (ADZUNA_APP_ID / ADZUNA_APP_KEY)
 * - Throttles requests so we stay inside the 25 requests/minute limit
 * - Retries once on 429 (rate limited) / 5xx
 * - Never throws - always returns a result array
 */
class AdzunaClient
{
    const BASE_URL = 'https://api.adzuna.com/v1/api';

    private $appId;
    private $appKey;
    private $country;

    /** @var float Seconds to wait between two calls */
    private $throttleSeconds = 0.4;

    /** @var int Number of HTTP calls made by this instance */
    private $callsMade = 0;

    /** @var array Collects human readable errors for logging */
    private $errors = [];

    /** @var float microtime of the last request */
    private $lastRequestAt = 0.0;

    public function __construct()
    {
        require_once __DIR__ . '/../config/EnvLoader.php';

        $this->appId   = (string) EnvLoader::get('ADZUNA_APP_ID', '');
        $this->appKey  = (string) EnvLoader::get('ADZUNA_APP_KEY', '');
        $this->country = (string) EnvLoader::get('ADZUNA_COUNTRY', 'in');
        $this->throttleSeconds = (float) EnvLoader::get('ADZUNA_THROTTLE_SECONDS', 0.4);

        if ($this->country === '') {
            $this->country = 'in';
        }
    }

    /**
     * True when both credentials are present.
     */
    public function isConfigured()
    {
        return $this->appId !== '' && $this->appKey !== '';
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getCallsMade()
    {
        return $this->callsMade;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Search job ads.
     *
     * @param array $params Adzuna query params (what, where, category, sort_by, max_days_old, ...)
     * @param int   $page   1-based page number
     * @return array{ok:bool, jobs:array, count:int, http:int, error:?string}
     */
    public function search(array $params = [], $page = 1)
    {
        $empty = ['ok' => false, 'jobs' => [], 'count' => 0, 'http' => 0, 'error' => null];

        if (!$this->isConfigured()) {
            $empty['error'] = 'Adzuna credentials are missing. Add ADZUNA_APP_ID and ADZUNA_APP_KEY to .env';
            return $empty;
        }

        $page = max(1, (int) $page);

        $query = array_merge([
            'app_id'             => $this->appId,
            'app_key'            => $this->appKey,
            'content-type'       => 'application/json',
            'results_per_page'   => max(1, min(50, (int) EnvLoader::get('ADZUNA_RESULTS_PER_PAGE', 50))),
        ], $params);

        $url = self::BASE_URL . '/jobs/' . rawurlencode($this->country)
             . '/search/' . $page . '?' . http_build_query($query);

        $result = $this->requestUrl($url);

        // One retry for transient failures / rate limiting
        if (!$result['ok'] && ($result['http'] === 429 || $result['http'] === 0 || $result['http'] >= 500)) {
            usleep((int) (1500000)); // 1.5s backoff
            $result = $this->requestUrl($url);
        }

        if (!$result['ok']) {
            return [
                'ok'    => false,
                'jobs'  => [],
                'count' => 0,
                'http'  => $result['http'],
                'error' => $result['error'],
            ];
        }

        $jobs = isset($result['data']['results']) && is_array($result['data']['results'])
            ? $result['data']['results']
            : [];

        return [
            'ok'    => true,
            'jobs'  => $jobs,
            'count' => count($jobs),
            'http'  => $result['http'],
            'error' => null,
        ];
    }

    /**
     * Category tags Adzuna uses for this country (handy for verifying ADZUNA_CATEGORY).
     *
     * @return array{ok:bool, results:array, error:?string}
     */
    public function categories()
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'results' => [], 'error' => 'Adzuna credentials are missing'];
        }

        $url = self::BASE_URL . '/jobs/' . rawurlencode($this->country) . '/categories?'
             . http_build_query([
                'app_id'       => $this->appId,
                'app_key'      => $this->appKey,
                'content-type' => 'application/json',
             ]);

        $result = $this->requestUrl($url);
        if (!$result['ok']) {
            return ['ok' => false, 'results' => [], 'error' => $result['error']];
        }

        return [
            'ok'      => true,
            'results' => $result['data']['results'] ?? [],
            'error'   => null,
        ];
    }

    /**
     * Perform one HTTP GET with throttling and JSON decoding.
     */
    private function requestUrl($url)
    {
        $this->throttle();

        $http = 0;
        $body = '';
        $error = null;

        if (!function_exists('curl_init')) {
            // Fallback when cURL is unavailable
            $ctx = stream_context_create([
                'http' => [
                    'timeout'      => 20,
                    'ignore_errors' => true,
                    'header'       => "Accept: application/json\r\nUser-Agent: ITINFOAcademyJobs/1.0\r\n",
                ],
                'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
            ]);
            $body = @file_get_contents($url, false, $ctx);
            if ($body === false) {
                $error = 'HTTP request failed (stream)';
            }
            $http = isset($http_response_header[0]) ? (int) substr($http_response_header[0], 9, 3) : 0;
        } else {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 25,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT      => 'ITINFOAcademyJobs/1.0',
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $body = curl_exec($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if ($body === false) {
                $error = 'cURL: ' . curl_error($ch);
            }
            curl_close($ch);
        }

        $this->callsMade++;

        if ($error !== null) {
            $this->errors[] = $error;
            return ['ok' => false, 'http' => $http, 'error' => $error, 'data' => null];
        }

        if ($http !== 200) {
            $message = 'Adzuna returned HTTP ' . $http;
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                if (!empty($decoded['error'])) {
                    $message .= ': ' . (is_string($decoded['error']) ? $decoded['error'] : json_encode($decoded['error']));
                } elseif (!empty($decoded['exception'])) {
                    $message .= ': ' . (is_string($decoded['exception']) ? $decoded['exception'] : json_encode($decoded['exception']));
                }
            } elseif (strlen($body) < 200 && trim($body) !== '') {
                $message .= ': ' . trim(strip_tags($body));
            }
            $this->errors[] = $message;
            return ['ok' => false, 'http' => $http, 'error' => $message, 'data' => null];
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            $message = 'Adzuna returned invalid JSON';
            $this->errors[] = $message;
            return ['ok' => false, 'http' => $http, 'error' => $message, 'data' => null];
        }

        return ['ok' => true, 'http' => $http, 'error' => null, 'data' => $data];
    }

    /**
     * Keep a small gap between calls (Adzuna allows ~25/min on the free tier).
     */
    private function throttle()
    {
        if ($this->throttleSeconds <= 0) {
            return;
        }
        if ($this->lastRequestAt > 0) {
            $elapsed = microtime(true) - $this->lastRequestAt;
            if ($elapsed < $this->throttleSeconds) {
                usleep((int) (($this->throttleSeconds - $elapsed) * 1000000));
            }
        }
        $this->lastRequestAt = microtime(true);
    }
}
