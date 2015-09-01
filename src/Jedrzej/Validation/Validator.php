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

        // replace placeholders in parameters
        foreach ($parameters as $index => $parameter) {
            if (preg_match('/{{([a-zA-Z0-9_\-\.]+)}}/', $parameter, $matches)) {
                $parameters[$index] = $this->getValue($matches[1]);
            }
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

    public function validateIf($attribute, $value, $parameters, Validator $validator)
    {
        $this->requireParameterCount(2, $parameters, 'if');

        preg_match_all('/([a-z_]+:[^:]+)(,|$)(?=$|[a-z_]+:)/', implode(',', array_slice($parameters, 1)), $matches);
        $rules = $matches[0];

        if (empty($rules)) {
            return true;
        }

        // all rules except the last one are applied to $otherValue
        $rulesToCheck = array_slice($rules, 0, count($rules) - 1);

        // last rule will be applied to $attribute
        // there might be some commas left from splitting
        $ruleToApply = trim($rules[count($rules) - 1], ',');

        // get the value of the other attribute
        $otherValue = array_get($validator->getData(), $parameters[0]);

        $rulesToCheck = array_map(function ($rule) {
            // there might be some commas left from splitting
            return trim($rule, ',');
        }, $rulesToCheck);

        // new instance of validator needs to be created so that failing validation of $otherValue doesn't fail validation of $value
        if (\Validator::make(['value' => $otherValue], ['value' => implode('|', $rulesToCheck)])->fails()) {
            return true;
        }

        $this->validate($attribute, $ruleToApply);

        return count($this->messages->all()) == 0;
    }
}