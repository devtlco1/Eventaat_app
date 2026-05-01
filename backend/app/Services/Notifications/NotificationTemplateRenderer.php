<?php

namespace App\Services\Notifications;

final class NotificationTemplateRenderer
{
    /**
     * Render a template string by replacing {{placeholders}} with provided values.
     *
     * - Unknown placeholders remain unchanged.
     * - Values are cast to strings.
     *
     * @param  array<string, mixed>  $data
     */
    public function render(string $template, array $data): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            function (array $m) use ($data): string {
                $key = (string) ($m[1] ?? '');

                if ($key === '') {
                    return (string) ($m[0] ?? '');
                }

                if (! array_key_exists($key, $data)) {
                    return (string) ($m[0] ?? '');
                }

                $value = $data[$key];

                if ($value === null) {
                    return '';
                }

                if (is_scalar($value)) {
                    return (string) $value;
                }

                $json = json_encode($value);

                return $json !== false ? $json : '';
            },
            $template
        );
    }
}

