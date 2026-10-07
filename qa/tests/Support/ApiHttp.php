<?php

/**
 * HTTP client for /api/ tests. Keeps its own cookie jar so a site login
 * and an LTI launch do not share a session.
 */
final class ApiHttp
{
    private string $cookieFile;

    public function __construct(private string $base)
    {
        $this->base = rtrim($base, '/');
        $file = tempnam(sys_get_temp_dir(), 'tsugi-api-');
        if ($file === false) {
            throw new RuntimeException('Could not create a cookie jar');
        }
        $this->cookieFile = $file;
    }

    public function __destruct()
    {
        if (is_file($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    /**
     * @param array{query?: array, headers?: array<string,string>, form?: array, body?: string, follow?: bool, timeout?: int} $options
     */
    public function request(string $method, string $pathOrUrl, array $options = []): ApiResponse
    {
        $url = $this->url($pathOrUrl);
        if (!empty($options['query'])) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($options['query']);
        }

        $headerLines = [];
        foreach ($options['headers'] ?? [] as $name => $value) {
            $headerLines[] = $name.': '.$value;
        }

        $statusLine = '';
        $headers = [];
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('curl_init failed for '.$url);
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => $options['follow'] ?? true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_USERAGENT => 'TsugiApiTest/1.0',
            CURLOPT_TIMEOUT => $options['timeout'] ?? 30,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$statusLine, &$headers) {
                $trim = trim($line);
                if ($trim === '') {
                    return strlen($line);
                }
                if (str_starts_with($trim, 'HTTP/')) {
                    $statusLine = $trim;
                    $headers = [];
                    return strlen($line);
                }
                $pos = strpos($trim, ':');
                if ($pos !== false) {
                    $name = strtolower(substr($trim, 0, $pos));
                    $headers[$name] = trim(substr($trim, $pos + 1));
                }
                return strlen($line);
            },
        ]);

        if (isset($options['form'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($options['form']));
            if (!isset($options['headers']['Content-Type'])) {
                $headerLines[] = 'Content-Type: application/x-www-form-urlencoded';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
            }
        } elseif (array_key_exists('body', $options)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['body']);
        }

        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('HTTP '.$method.' '.$url.' failed: '.$error);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $effective = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        unset($ch);

        return new ApiResponse($status, $statusLine, $headers, (string) $body, $effective);
    }

    public function url(string $pathOrUrl): string
    {
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            return $pathOrUrl;
        }
        return $this->base.'/'.ltrim($pathOrUrl, '/');
    }
}

final class ApiResponse
{
    /**
     * @param array<string,string> $headers
     */
    public function __construct(
        public int $status,
        public string $statusLine,
        public array $headers,
        public string $body,
        public string $effectiveUrl
    ) {
    }

    public function header(string $name): string
    {
        return $this->headers[strtolower($name)] ?? '';
    }

    public function json(): mixed
    {
        return json_decode($this->body);
    }

    public function excerpt(int $limit = 500): string
    {
        $text = preg_replace('/\s+/u', ' ', $this->body) ?? $this->body;
        if (strlen($text) > $limit) {
            return substr($text, 0, $limit).'…';
        }
        return $text;
    }
}
