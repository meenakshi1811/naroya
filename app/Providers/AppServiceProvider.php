<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         Validator::extend('valid_email_domain', function ($attribute, $value, $parameters, $validator) {
            // List of allowed email domains
            $allowedDomains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com'];
    
            // Extract the domain from the email
            $emailParts = explode('@', $value);
    
            // If email is malformed or domain is not part of the allowed domains list, return false
            if (count($emailParts) != 2) {
                return false;
            }
    
            $domain = array_pop($emailParts);
    
            // Check if the domain is in the allowed list
            return in_array(strtolower($domain), $allowedDomains);
        });

        Validator::extend('valid_indian_mobile', function ($attribute, $value, $parameters, $validator) {
            if (! is_string($value) || ! preg_match('/^[6-9][0-9]{9}$/', $value)) {
                return false;
            }

            // Reject obvious fake numbers such as 6666666666 or 4444444444.
            if (preg_match('/^(\d)\1{9}$/', $value)) {
                return false;
            }

            return true;
        });

        Validator::replacer('valid_indian_mobile', function ($message, $attribute, $rule, $parameters) {
            return 'Please enter a valid Indian mobile number (10 digits, starting with 6, 7, 8, or 9).';
        });
        Passport::enablePasswordGrant();
    }


}
