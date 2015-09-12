# Extended Validation for Laravel 5

## THIS PACKAGE IS STILL A WORK IN PROGRESS. IT HASN'T BEEN TESTED. USE AT YOUR OWN RISK :)

This package extends Laravel's validation syntax with the following:

- aliasing validation rule configurations
- negating validation rules
- conditional validation rules
- using automatically replaced placeholders
- validating array and JSON structure
- validationg lists

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

### Aliasing validation rules

It is possible to alias often used rule configuration to allow for reuse. This is an alternative to writing custom validation rules.

```php
    // validate if string is a hex calue
    Validator::alias('hex', 'regex:^[0-9a-fA-F]+$);
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

### Placeholders in validation rules

If validation of one field needs to use the value of another field as parameter, you can use a `{{parameter_name}}` placeholder in rule definition instead of parameter value.
Value of corresponding field will be passed to validator instead of the placeholder. If the corresponding value is missing in
validated data set, the value will be taken from Config.

```php
    $rules = [
        'user_id' => 'exists:users,id'
        'email'   => 'unique:users,email,{{user_id}},
        'age'     => 'min:{{app.min_age}}
    ];
```

### Validating arrays and JSON fields

Package offers new syntax for validationg array structures and adds such possibility for JSON fields.

```php
   $data = [
     // someArrayField needs to be array that contains age and email
     'someArrayField' => 'array:@someRules',
     // someJSONField needs to be valid JSON that contains age and email
     'someJSONField'  => 'json:@someRules',
     '@someRules' => [
       'age' => 'required|numeric',
       'email' => 'required|email'
     ],
   ];
```

### Validating lists of values

It is possible to validate lists of value - defined set of rules will be applied to each entry in the array. 
Syntax for defining tue rules is similar to validating arrays and JSON.

```php
   $data = [
     // someArrayField contains multiple arrays, that must contain username and email field
     'someArrayField' => 'list:@someRules',
     '@someRules' => [
       'username' => 'required',
       'email' => 'required|email'
     ],
   ];
```