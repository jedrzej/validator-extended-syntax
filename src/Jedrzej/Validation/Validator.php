<?php namespace Jedrzej\Validation;

use Illuminate\Validation\Validator as BaseValidator;
use Symfony\Component\Translation\TranslatorInterface;

class Validator extends BaseValidator
{
    public function __construct(TranslatorInterface $translator, array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        $this->implicitRules[] = 'If';
        parent::__construct($translator, $data, $rules, $messages, $customAttributes);
    }

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

    protected function validateIf($attribute, $value, $parameters, Validator $validator)
    {
        $this->requireParameterCount(2, $parameters, 'if');

        //preg_match_all('/([a-z_]+:[^:]+)(,|$)(?=$|[a-z_]+:)/', implode(',', array_slice($parameters, 1)), $matches);
        $rule = implode(',', $parameters);
        if (!preg_match('/^([a-zA-z_]+,[a-zA-z_]+(:([0-9a-zA-z_]+,?)+);?)*[a-zA-z_]+(:([0-9a-zA-z_]+,?)+)?$/', $rule)) {
            throw new InvalidArgumentException("Invalid validateIf syntax: " . $rule);
        }

        $allRules = explode(';', $rule);

        if (empty($allRules)) {
            return true;
        }

        // last rule will be applied to $attribute
        $ruleToApply = $allRules[count($allRules) - 1];

        // all rules except the last one are applied to $otherValue
        $rulesToCheck = array_slice($allRules, 0, count($allRules) - 1);

        // build validation array
        $rules = [];
        foreach ($rulesToCheck as $ruleToCheck) {
            list($field, $rule) = explode(',', $ruleToCheck, 2);
            if ($field != $attribute) {
                $rules[$field][] = $rule;
            }
        }

        $rules = array_map(function($r) {
            return implode('|', $r);
        }, $rules);

        // new instance of validator needs to be created so that failing validation of $otherValue doesn't fail validation of $value
        if (\Validator::make($this->getData(), $rules)->fails()) {
            return true;
        }

        $this->validate($attribute, $ruleToApply);

        return count($this->messages->all()) == 0;
    }

    protected function validateEmpty($attribute, $value)
    {
        return empty($value);
    }

    protected function validateEquals($attribute, $value, $parameters) {
        $this->requireParameterCount(1, $parameters, 'equals');

        return $this->validateIn($attribute, $value, (array)$parameters[0]);
    }
}