# Extended Validation for Laravel 4/5

This package adds negation syntax to Laravel's validation component.

## Composer install

Add the following line to `composer.json` file in your project:

    "jedrzej/validator-extended-syntax": "0.0.1"

or run the following in the commandline in your project's root folder:

    composer require "jedrzej/validator-extended-syntax" "0.0.1"

## Usage

In order to extend validator syntax, you need to register `ValidationServiceProvider` in your `config/app.php`:

    'providers' => [
        ...
        /*
         * Custom proviers
         */
        'Jedrzej\Validation\ValidationServiceProvider'
    ];

When defining validation rules, you can negate chosen rule by prepending its name with exclamation mark.
Negated validation rules will fail when not negated rule would pass and vice versa.

    $rules = ['string' => 'min:3']; //validate if string is at least 3 characters long
    $data = ['string' => 'abcde'];
    $result = Validator::make($data, $rules)->passes(); // TRUE,

    $rules = ['string' => 'min:3'];
    $data = ['string' => 'ab'];
    $result = Validator::make($data, $rules)->passes(); // FALSE,

    $rules = ['string' => '!min:3'];
    $data = ['string' => 'abcde'];
    $result = Validator::make($data, $rules)->passes(); // FALSE,

    $rules = ['string' => '!min:3'];
    $data = ['string' => 'ab'];
    $result = Validator::make($data, $rules)->passes(); // TRUE,
