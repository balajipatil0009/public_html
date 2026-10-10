<?php
/**
 * Simple Environment Variable Loader
 * Loads variables from .env file and makes them available via getenv()
 */
class EnvLoader
{
    /**
     * Load environment variables from .env file
     * 
     * @param string $path Path to .env file
     * @return bool Success status
     */
    public static function load($path)
    {
        if (!file_exists($path)) {
            error_log("Environment file not found: $path");
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE format
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);

                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                $value = self::removeQuotes($value);

                // Set environment variable
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        return true;
    }

    /**
     * Remove surrounding quotes from value
     * 
     * @param string $value
     * @return string
     */
    private static function removeQuotes($value)
    {
        if (
            (substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")
        ) {
            return substr($value, 1, -1);
        }
        return $value;
    }

    /**
     * Get environment variable with fallback
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }

    /**
     * Require environment variable (throw exception if not found)
     * 
     * @param string $key
     * @return string
     * @throws Exception
     */
    public static function require($key)
    {
        $value = getenv($key);
        if ($value === false) {
            throw new Exception("Required environment variable not found: $key");
        }
        return $value;
    }
}
