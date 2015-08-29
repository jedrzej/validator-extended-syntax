<?php namespace Jedrzej\Validation;

use Illuminate\Support\ServiceProvider;
Use Illuminate\Validation\Factory;

class ValidationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Factory::resolver(function ($translator, array $data, array $rules, array $messages, array $customAttributes) {
            return new Validator($translator, $data, $rules, $messages, $customAttributes);
        });
    }

    public function register()
    {
        //
    }
}
