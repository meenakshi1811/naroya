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
        Passport::enablePasswordGrant();
    }


}
