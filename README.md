# Extended Validation for Laravel 5

This package extends Laravel's validation syntax with the following:

- aliasing validation rule configurations
- negating validation rules
- using automatically replaced placeholders

## Composer install

Add the following line to `composer.json` file in your project:

    "jedrzej/validator-extended-syntax": "0.0.2"

or run the following in the commandline in your project's root folder:

    composer require "jedrzej/validator-extended-syntax" "0.0.2"

## Usage

In order to extend validator syntax, you need to register `ValidationServiceProvider` in your `config/app.php`:

```php
    'providers' => [
        ...
        /*
         * Custom proviers
         */
        'Jedrzej\Validation\ValidationServiceProvider'
    ];
```

### Aliasing validation rules

It is possible to alias often used rule configuration to allow for reuse. This is an alternative to writing custom validation rules.

```php
    // validate if string is a hex calue
    Validator::alias('hex', 'regex:/^[0-9a-fA-F]+$/');
    $rules = [
      'value' => 'hex'
    ];
    $validator = Validator::make($data, $rules);

    // passing arguments to aliases
    // validate number is a positive integer no larger than 100
    Validator::alias('positive_limited', 'between:1,?');
    $rules = [
      'value' => 'positive_limited:100'
    ];
    $validator = Validator::make($data, $rules);

    // record exists with is_active flag set
    Validator::alias('active_exists', 'exists:?,?,is_active,1');
    $rules = [
      'user_id' => 'active_exists:users,id'
    ];
    $validator = Validator::make($data, $rules);
```

### Negating validation results

When defining validation rules, you can negate chosen rule by prepending its name with exclamation mark.
Negated validation rules will fail when not negated rule would pass and vice versa.

```php
    $rules = ['string' => 'min:3']; //validate if string is at least 3 characters long
    $data = ['string' => 'abcde'];
    $result = Validator::make($data, $rules)->passes(); // TRUE

    $rules = ['string' => 'min:3'];
    $data = ['string' => 'ab'];
    $result = Validator::make($data, $rules)->passes(); // FALSE

    $rules = ['string' => '!min:3'];
    $data = ['string' => 'abcde'];
    $result = Validator::make($data, $rules)->passes(); // FALSE

    $rules = ['string' => '!min:3'];
    $data = ['string' => 'ab'];
    $result = Validator::make($data, $rules)->passes(); // TRUE
```

### Placeholders in validation rules

If validation of one field needs to use the value of another field as parameter, you can use a `{{parameter_name}}` placeholder in rule definition instead of parameter value.
Value of corresponding field will be passed to validator instead of the placeholder. If the corresponding value is missing in
validated data set, the value will be taken from Config.

```php
    $rules = [
        'user_id' => 'exists:users,id'
        'email'   => 'unique:users,email,{{user_id}}',
        'age'     => 'min:{{app.min_age}}
    ];
```