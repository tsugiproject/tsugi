<?php

declare(strict_types=1);

/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Carbon\Traits;

use Carbon\Exceptions\InvalidFormatException;
use Carbon\FactoryImmutable;
use DateTimeZone;
use ReturnTypeWillChange;
use Throwable;

/**
 * Trait Serialization.
 *
 * Serialization and JSON stuff.
 *
 * Depends on the following properties:
 *
 * @property int $year
 * @property int $month
 * @property int $daysInMonth
 * @property int $quarter
 *
 * Depends on the following methods:
 *
 * @method string|static locale(string $locale = null, string ...$fallbackLocales)
 * @method string        toJSON()
 */
trait Serialization
{
    use ObjectInitialisation;

    /**
     * List of key to use for dump/serialization.
     *
     * @var string[]
     */
    protected array $dumpProperties = ['date', 'timezone_type', 'timezone'];

    /**
     * Locale to dump comes here before serialization.
     *
     * @var string|null
     */
    protected $dumpLocale;

    /**
     * Embed date properties to dump in a dedicated variables so it won't overlap native
     * DateTime ones.
     *
     * @var array|null
     */
    protected $dumpDateProperties;

    /**
     * Return a serialized string of the instance.
     */
    public function serialize(): string
    {
        return serialize($this);
    }

    /**
     * Create an instance from a serialized string.
     *
     * @param string $value
     *
     * @throws InvalidFormatException
     *
     * @return static
     */
    public static function fromSerialized($value): static
    {
        $instance = @unserialize((string) $value);

        if (!$instance instanceof static) {
            throw new InvalidFormatException("Invalid serialized value: $value");
        }

        return $instance;
    }

    /**
     * The __set_state handler.
     *
     * @param string|array $dump
     *
     * @return static
     */
    #[ReturnTypeWillChange]
    public static function __set_state($dump): static
    {
        if (\is_string($dump)) {
            return static::parse($dump);
        }

        /** @var \DateTimeInterface $date */
        $date = get_parent_class(static::class) && method_exists(parent::class, '__set_state')
            ? parent::__set_state((array) $dump)
            : (object) $dump;

        return static::instance($date);
    }

    /**
     * Returns the list of properties to dump on serialize() called on.
     *
     * Only used by PHP < 7.4.
     *
     * @return array
     */
    public function __sleep()
    {
        $properties = $this->getSleepProperties();

        if ($this->localTranslator ?? null) {
            $properties[] = 'dumpLocale';
            $this->dumpLocale = $this->locale ?? null;
        }

        return $properties;
    }

    /**
     * Returns the values to dump on serialize() called on.
     *
     * Only used by PHP >= 7.4.
     *
     * @return array
     */
    public function __serialize(): array
    {
        // @codeCoverageIgnoreStart
        if (isset($this->timezone_type, $this->timezone, $this->date)) {
            return [
                'date' => $this->date,
                'timezone_type' => $this->timezone_type,
                'timezone' => $this->dumpTimezone($this->timezone),
            ];
        }
        // @codeCoverageIgnoreEnd

        $timezone = $this->getTimezone();
        $export = [
            'date' => $this->format('Y-m-d H:i:s.u'),
            'timezone_type' => $timezone->getType(),
            'timezone' => $timezone->getName(),
        ];

        // @codeCoverageIgnoreStart
        if (\extension_loaded('msgpack') && isset($this->constructedObjectId)) {
            $timezone = $this->timezone ?? null;
            $export['dumpDateProperties'] = [
                'date' => $this->format('Y-m-d H:i:s.u'),
                'timezone' => $this->dumpTimezone($timezone),
            ];
        }
        // @codeCoverageIgnoreEnd

        if ($this->localTranslator ?? null) {
            $export['dumpLocale'] = $this->locale ?? null;
        }

        return $export;
    }

    /**
     * Set locale if specified on unserialize() called.
     *
     * Only used by PHP < 7.4.
     */
    public function __wakeup(): void
    {
        if (parent::class && method_exists(parent::class, '__wakeup')) {
            // @codeCoverageIgnoreStart
            try {
                parent::__wakeup();
            } catch (Throwable $exception) {
                try {
                    // FatalError occurs when calling msgpack_unpack() in PHP 7.4 or later.
                    ['date' => $date, 'timezone' => $timezone] = $this->dumpDateProperties;
                    parent::__construct($date, $timezone);
                } catch (Throwable) {
                    throw $exception;
                }
            }
            // @codeCoverageIgnoreEnd
        }

        $this->constructedObjectId = spl_object_hash($this);

        if (isset($this->dumpLocale)) {
            $this->locale($this->dumpLocale);
            $this->dumpLocale = null;
        }

        $this->cleanupDumpProperties();
    }

    /**
     * Set locale if specified on unserialize() called.
     *
     * Only used by PHP >= 7.4.
     */
    public function __unserialize(array $data): void
    {
        // @codeCoverageIgnoreStart
        try {
            $this->__construct($data['date'] ?? null, $data['timezone'] ?? null);
        } catch (Throwable $exception) {
            if (!isset($data['dumpDateProperties']['date'], $data['dumpDateProperties']['timezone'])) {
                throw $exception;
            }

            try {
                // FatalError occurs when calling msgpack_unpack() in PHP 7.4 or later.
                ['date' => $date, 'timezone' => $timezone] = $data['dumpDateProperties'];
                $this->__construct($date, $timezone);
            } catch (Throwable) {
                throw $exception;
            }
        }
        // @codeCoverageIgnoreEnd

        if (isset($data['dumpLocale'])) {
            $this->locale($data['dumpLocale']);
        }
    }

    /**
     * Prepare the object for JSON serialization.
     */
    public function jsonSerialize(): mixed
    {
        $serializer = $this->localSerializer
            ?? $this->getFactory()->getSettings()['toJsonFormat']
            ?? null;

        if ($serializer) {
            return \is_string($serializer)
                ? $this->rawFormat($serializer)
                : $serializer($this);
        }

        return $this->toJSON();
    }

    /**
     * @deprecated To avoid conflict between different third-party libraries, static setters should not be used.
     *             You should rather transform Carbon object before the serialization.
     *
     * JSON serialize all Carbon instances using the given callback.
     */
    public static function serializeUsing(string|callable|null $format): void
    {
        FactoryImmutable::getDefaultInstance()->serializeUsing($format);
    }

    /**
     * Cleanup properties attached to the public scope of DateTime when a dump of the date is requested.
     * foreach ($date as $_) {}
     * serializer($date)
     * var_export($date)
     * get_object_vars($date)
     */
    public function cleanupDumpProperties(): self
    {
        // @codeCoverageIgnoreStart
        if (PHP_VERSION < 8.2) {
            foreach ($this->dumpProperties as $property) {
                if (isset($this->$property)) {
                    unset($this->$property);
                }
            }
        }
        // @codeCoverageIgnoreEnd

        return $this;
    }

    private function getSleepProperties(): array
    {
        $properties = $this->dumpProperties;

        // @codeCoverageIgnoreStart
        if (!\extension_loaded('msgpack')) {
            return $properties;
        }

        if (isset($this->constructedObjectId)) {
            $timezone = $this->timezone ?? null;
            $this->dumpDateProperties = [
                'date' => $this->format('Y-m-d H:i:s.u'),
                'timezone' => $this->dumpTimezone($timezone),
            ];

            $properties[] = 'dumpDateProperties';
        }

        return $properties;
        // @codeCoverageIgnoreEnd
    }

    private function dumpTimezone(mixed $timezone): mixed
    {
        return $timezone instanceof DateTimeZone ? $timezone->getName() : $timezone;
    }
}
