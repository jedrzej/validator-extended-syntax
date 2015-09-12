<?php namespace Jedrzej\Validation;

use Illuminate\Validation\Validator as BaseValidator;
use InvalidArgumentException;
use Symfony\Component\Translation\TranslatorInterface;

class Validator extends BaseValidator
{
    protected $aliases = [];

    public function __construct(TranslatorInterface $translator, array $data, array $rules, array $messages = [], array $customAttributes = [], array $aliases = [])
    {
        $this->implicitRules[] = 'If';
        $this->aliases = $aliases;
        parent::__construct($translator, $data, $rules, $messages, $customAttributes);
    }

    protected function validate($attribute, $rule)
    {
        $negated = preg_match('/^!/', $rule);
        $rule = $negated ? substr($rule, 1) : $rule;

        list($rule, $parameters) = $this->parseRule($rule);

        if ($this->isAlias($rule)) {
            list($rule, $parameters) = $this->parseAlias($rule, $parameters);
        }

        if ($rule == '') {
            return;
        }

        // replace placeholders in parameters
        foreach ($parameters as $index => $parameter) {
            if (preg_match('/{{([a-zA-Z0-9_\-\.]+)}}/', $parameter, $matches)) {
                if (!is_null($value = $this->getValue($matches[1]))) {
                    $parameters[$index] = $value;
                } else if (!is_null($value = Config::get($matches[1]))) {
                    $parameters[$index] = $value;
                } else {
                    throw new InvalidArgumentException(sprintf('No value available for placeholder "%s".', $matches[1]));
                }
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

        $rules = array_map(function ($r) {
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

    protected function validateEquals($attribute, $value, $parameters)
    {
        $this->requireParameterCount(1, $parameters, 'equals');

        return $this->validateIn($attribute, $value, (array)$parameters[0]);
    }

    protected function validateArray($attribute, $value, $parameters)
    {
        if (!is_array($value)) return false;

        if (!count($parameters) || !array_key_exists($parameters[0], $this->rules)) return true;

        $rules = $this->rules[$parameters[0]];

        $validator = \Validator::make($value, $rules);
        if ($validator->fails()) {
            foreach ($validator->messages()->getMessages() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->messages()->add(sprintf('%s.%s', $attribute, $key), $message);
                }
            }
        }

        return true;
    }

    protected function validateJson($attribute, $value, $parameters)
    {
        $value = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($parameters)) {
            return json_last_error() !== JSON_ERROR_NONE;
        }

        return $this->validateArray($attribute, $value, $parameters);
    }

    public function passes()
    {
        $this->messages = new MessageBag;

        foreach ($this->rules as $attribute => $rules) {
            if (preg_match('/^@/', $attribute)) {
                continue;
            }

            foreach ($rules as $rule) {
                $this->validate($attribute, $rule);
            }
        }

        foreach ($this->after as $after) {
            call_user_func($after);
        }

        return count($this->messages->all()) === 0;
    }

    protected function isAlias($rule) {
        return array_key_exists($rule, $this->aliases);
    }

    protected function parseAlias($rule, array $parameters = []) {
        $aliasedRule = $this->aliases[$rule];
        while(preg_match('/\?/', $aliasedRule) && !empty($parameters)) {
            $aliasedRule = preg_replace('/\?/', array_shift($parameters), $aliasedRule, 1);
        }

        if (!empty($parameters)) {
            $aliasedRule .= ',' . implode(',', $parameters);
        }

        return $this->parseRule($aliasedRule);
    }

}