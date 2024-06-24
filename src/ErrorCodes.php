<?php

namespace Drewlabs\Envoyer\Drivers\NGHCorp;

class ErrorCodes
{
    /**
     * Return error string mathing the error code
     * 
     * @param int $code 
     * @return string 
     */
    public static function message(int $code)
    {
        /** List of possible error code and their corresponding description */
        $values = [
            "100" => "Only POST is allowed.",
            "101" => "Invalid JSON",
            "102" => "Missing credentials",
            "103" => "Invalid credentials",
            "104" => "No data",
            "105" => "Missing from parameter.",
            "106" => "Sender ID error",
            "107" => "Missing message.",
            "108" => "Missing to number",
            "109" => "Invalid to number",
            "110" => "Route not found",
            "111" => "Insufficient credit",
            "112" => "Billing problem",
            "113" => "Unable to send sms - internal error"
        ];

        return $values[strval($code)] ?? 'Unknown error';
    }
}
