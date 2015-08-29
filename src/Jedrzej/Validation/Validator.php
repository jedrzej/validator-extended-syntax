<?php namespace Jedrzej\Validation;

use Illuminate\Validation\Validator as BaseValidator;

class Validator extends BaseValidator
{
    protected function validate($attribute, $rule)
    {
        $negated = preg_match('/^!/', $rule);
        $rule = $negated ? substr($rule, 1) : $rule;

        list($rule, $parameters) = $this->parseRule($rule);

        if ($rule == '') {
            return;
        }

        // We will get the value for the given attribute from the array of data and then
        // verify that the attribute is indeed validatable. Unless the rule implies
        // that the attribute is required, rules are not run for missing values.
        $value = $this->getValue($attribute);

        $validatable = $this->isValidatable($rule, $attribute, $value);

        $method = "validate{$rule}";

        if ($validatable && ($this->$method($attribute, $value, $parameters, $this) == $negated)) {
            $this->addFailure($attribute, $rule, $parameters);
        }
    }
}