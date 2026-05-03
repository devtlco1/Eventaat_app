<?php

namespace App\Exceptions;

use RuntimeException;

final class MissingTwilioWhatsAppOtpConfigurationException extends RuntimeException
{
    /**
     * @param  list<string>  $missingEnvKeys
     */
    public static function forKeys(array $missingEnvKeys): self
    {
        $keys = implode(', ', $missingEnvKeys);

        return new self(sprintf(
            'Twilio WhatsApp OTP is enabled (OTP_DRIVER=twilio_whatsapp) but required configuration is missing: %s. Set these in your environment.',
            $keys,
        ));
    }
}
