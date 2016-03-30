<?php namespace Jedrzej\Validation;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator as BaseValidator;
use Symfony\Component\Translation\TranslatorInterface;

class Validator extends BaseValidator
{
    protected $aliases = [];

    public function __construct(TranslatorInterface $translator, array $data, array $rules, array $messages = [], array $customAttributes = [], array $aliases = [])
    {
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
                    $parameters[$index] = 'NULL';
                }
            }
        }

        // We will get the value for the given attribute from the array of data and then
        // verify that the attribute is indeed validatable. Unless the rule implies
        // that the attribute is required, rules are not run for missing values.
        $value = $this->getValue($attribute);

        $validatable = $this->isValidatable($rule, $attribute, $value);

        $method = 'validate' . $rule;

        if ($validatable && ($this->$method($attribute, $value, $parameters, $this) == $negated)) {
            $this->addFailure($attribute, $rule, $parameters);
        }
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