<?php namespace Jedrzej\Validation;

use Illuminate\Validation\ValidationServiceProvider as BaseValidationServiceProvider;

class ValidationServiceProvider extends BaseValidationServiceProvider
{
    protected function registerValidationFactory()
    {
        $this->app->singleton('validator', function ($app) {
            $validator = new Factory($app['translator'], $app);

            // The validation presence verifier is responsible for determining the existence
            // of values in a given data collection, typically a relational database or
            // other persistent data stores. And it is used to check for uniqueness.
            if (isset($app['validation.presence'])) {
                $validator->setPresenceVerifier($app['validation.presence']);
            }

            return $validator;
        });
    }
}
