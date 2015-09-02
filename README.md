# Extended Validation for Laravel 4/5

This package adds negation syntax to Laravel's validation component.

## Composer install

Add the following line to `composer.json` file in your project:

    "jedrzej/validator-extended-syntax": "0.0.1"

or run the following in the commandline in your project's root folder:

    composer require "jedrzej/validator-extended-syntax" "0.0.1"

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

### Negating validation results

When defining validation rules, you can negate chosen rule by prepending its name with exclamation mark.
Negated validation rules will fail when not negated rule would pass and vice versa.

```php
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
```

### Placeholders in validation rules

If validation of one field needs to use the value of another field as parameter, you can use a `{{parameter_name}}` placeholder in rule definition instead of parameter value.
Value of corresponding field will be passed to validator instead of the placeholder.

```php
    $rules = [
        'user_id' => 'exists:users,id'
        'email'   => 'unique:users,email,{{user_id}}
    ];
```

### Conditional validation

It is possible to apply selected validation rules to a field only if some other rules pass - either on the same or some other field
- using `if` validation. It takes unlimited amount of parameters - the last one being the rule to apply and all the previous
ones being conditions that need to be met for the validation to be applied. Detailed syntax is:

```php
    if:<condition>;<condition>;...,<condition>;<validation>
    <condition> := <field_name>,<validation>
    <validation> := <rule_name>:<rule_parameter>,<rule_parameter>,...
```

Some examples:

```php
    // email must must be unique only if user is active
    $rules = [
      'is_active' => 'boolean',
      'email'     => 'if:is_active,accepted;unique:users,email'
    ];

    // email must be set if user is active and and has account_type equal to admin or moderator
    $rules = [
      'is_active'    => 'boolean',
      'account_type' => 'in:guest,user,moderator,admin',
      'email'        => 'if:is_active,accepted;account_type,required;account_type,in:admin,moderator;required'
    ];
```