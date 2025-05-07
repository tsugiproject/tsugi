<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\BrowserKit;

use Symfony\Component\BrowserKit\Exception\InvalidArgumentException;
use Symfony\Component\BrowserKit\Exception\UnexpectedValueException;

/**
 * Cookie represents an HTTP cookie.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Cookie
{
    /**
     * Handles dates as defined by RFC 2616 section 3.3.1, and also some other
     * non-standard, but common formats.
     */
    private const DATE_FORMATS = [
        'D, d M Y H:i:s T',
        'D, d-M-y H:i:s T',
        'D, d-M-Y H:i:s T',
        'D, d-m-y H:i:s T',
        'D, d-m-Y H:i:s T',
        'D M j G:i:s Y',
        'D M d H:i:s Y T',
    ];

    protected string $value;
    protected ?string $expires = null;
    protected string $path;
    protected string $rawValue;

    /**
     * Sets a cookie.
     *
     * @param string      $name         The cookie name
     * @param string|null $value        The value of the cookie
     * @param string|null $expires      The time the cookie expires
     * @param string|null $path         The path on the server in which the cookie will be available on
     * @param string      $domain       The domain that the cookie is available
     * @param bool        $secure       Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client
     * @param bool        $httponly     The cookie httponly flag
     * @param bool        $encodedValue Whether the value is encoded or not
     * @param string|null $samesite     The cookie samesite attribute
     */
    public function __construct(
        private string $name,
        ?string $value,
        ?string $expires = null,
        ?string $path = null,
        private string $domain = '',
        private bool $secure = false,
        private bool $httponly = true,
        bool $encodedValue = false,
        private ?string $samesite = null,
    ) {
        if ($encodedValue) {
            $this->rawValue = $value ?? '';
            $this->value = urldecode($this->rawValue);
        } else {
            $this->value = $value ?? '';
            $this->rawValue = rawurlencode($this->value);
        }
        $this->path = $path ?: '/';

        if (null !== $expires) {
            $timestampAsDateTime = \DateTimeImmutable::createFromFormat('U', $expires);
            if (false === $timestampAsDateTime) {
                throw new UnexpectedValueException(\sprintf('The cookie expiration time "%s" is not valid.', $expires));
            }

            $this->expires = $timestampAsDateTime->format('U');
        }
    }

    /**
     * Returns the HTTP representation of the Cookie.
     */
    public function __toString(): string
    {
        $cookie = \sprintf('%s=%s', $this->name, $this->rawValue);

        if (null !== $this->expires) {
            $dateTime = \DateTimeImmutable::createFromFormat('U', $this->expires, new \DateTimeZone('GMT'));
            $cookie .= '; expires='.str_replace('+0000', '', $dateTime->format(self::DATE_FORMATS[0]));
        }

        if ('' !== $this->domain) {
            $cookie .= '; domain='.$this->domain;
        }

        if ($this->path) {
            $cookie .= '; path='.$this->path;
        }

        if ($this->secure) {
            $cookie .= '; secure';
        }

        if ($this->httponly) {
            $cookie .= '; httponly';
        }

        if (null !== $this->samesite) {
            $cookie .= '; samesite='.$this->samesite;
        }

        return $cookie;
    }

    /**
     * Creates a Cookie instance from a Set-Cookie header value.
     *
     * @throws InvalidArgumentException
     */
    public static function fromString(string $cookie, ?string $url = null): static
    {
        $parts = explode(';', $cookie);

        if (!str_contains($parts[0], '=')) {
            throw new InvalidArgumentException(\sprintf('The cookie string "%s" is not valid.', $parts[0]));
        }

        [$name, $value] = explode('=', array_shift($parts), 2);

        $values = [
            'name' => trim($name),
            'value' => trim($value),
            'expires' => null,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
            'passedRawValue' => true,
            'samesite' => null,
        ];

        if (null !== $url) {
            if (false === ($urlParts = parse_url($url)) || !isset($urlParts['host'])) {
                throw new InvalidArgumentException(\sprintf('The URL "%s" is not valid.', $url));
            }

            $values['domain'] = $urlParts['host'];
            $values['path'] = isset($urlParts['path']) ? substr($urlParts['path'], 0, strrpos($urlParts['path'], '/')) : '';
        }

        foreach ($parts as $part) {
            $part = trim($part);

            if ('secure' === strtolower($part)) {
                // Ignore the secure flag if the original URI is not given or is not HTTPS
                if (null === $url || !isset($urlParts['scheme']) || 'https' !== $urlParts['scheme']) {
                    continue;
                }

                $values['secure'] = true;

                continue;
            }

            if ('httponly' === strtolower($part)) {
                $values['httponly'] = true;

                continue;
            }

            if (2 === \count($elements = explode('=', $part, 2))) {
                if ('expires' === strtolower($elements[0])) {
                    $elements[1] = self::parseDate($elements[1]);
                }

                $values[strtolower($elements[0])] = $elements[1];
            }
        }

        return new static(
            $values['name'],
            $values['value'],
            $values['expires'],
            $values['path'],
            $values['domain'],
            $values['secure'],
            $values['httponly'],
            $values['passedRawValue'],
            $values['samesite']
        );
    }

    private static function parseDate(string $dateValue): ?string
    {
        // trim single quotes around date if present
        if (($length = \strlen($dateValue)) > 1 && "'" === $dateValue[0] && "'" === $dateValue[$length - 1]) {
            $dateValue = substr($dateValue, 1, -1);
        }

        foreach (self::DATE_FORMATS as $dateFormat) {
            if (false !== $date = \DateTimeImmutable::createFromFormat($dateFormat, $dateValue, new \DateTimeZone('GMT'))) {
                return $date->format('U');
            }
        }

        // attempt a fallback for unusual formatting
        if (false !== $date = date_create_immutable($dateValue, new \DateTimeZone('GMT'))) {
            return $date->format('U');
        }

        return null;
    }

    /**
     * Gets the name of the cookie.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the value of the cookie.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Gets the raw value of the cookie.
     */
    public function getRawValue(): string
    {
        return $this->rawValue;
    }

    /**
     * Gets the expires time of the cookie.
     */
    public function getExpiresTime(): ?string
    {
        return $this->expires;
    }

    /**
     * Gets the path of the cookie.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Gets the domain of the cookie.
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Returns the secure flag of the cookie.
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * Returns the httponly flag of the cookie.
     */
    public function isHttpOnly(): bool
    {
        return $this->httponly;
    }

    /**
     * Returns true if the cookie has expired.
     */
    public function isExpired(): bool
    {
        return null !== $this->expires && 0 != $this->expires && $this->expires <= time();
    }

    /**
     * Gets the samesite attribute of the cookie.
     */
    public function getSameSite(): ?string
    {
        return $this->samesite;
    }
}
