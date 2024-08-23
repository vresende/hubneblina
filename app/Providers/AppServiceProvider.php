<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registre qualquer serviço de aplicação aqui
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewPulse', function (User $user) {
            return true;
        });
        // Validação para XML
        Validator::extend('xml', function ($attribute, $value, $parameters, $validator) {
            try {
                new \SimpleXMLElement($value);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });

        Validator::replacer('xml', function ($message, $attribute, $rule, $parameters) {
            return Str::replace(':attribute', $attribute, 'The :attribute must be a valid XML.');
        });

        // Validação para JSON ou XML
        Validator::extend('json_or_xml', function ($attribute, $value, $parameters, $validator) {
            // Verifica se a string é um JSON válido
            json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                return true;
            }

            // Verifica se a string é um XML válido
            try {
                new \SimpleXMLElement($value);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        });

        Validator::replacer('json_or_xml', function ($message, $attribute, $rule, $parameters) {
            return Str::replace(':attribute', $attribute, 'The :attribute must be a valid JSON or XML.');
        });
    }
}
