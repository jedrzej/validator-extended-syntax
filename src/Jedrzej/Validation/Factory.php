<?php namespace Jedrzej\Validation;

use Illuminate\Support\Str;
use Illuminate\Validation\Factory as BaseFactory;

class Factory extends BaseFactory
{
    /**
     * All of the custom validation rules aliases.
     *
     * @var array
     */
    protected $aliases = [];

    public function alias($alias, $rule) {{}
        $this->aliases[Str::studly($alias)] = $rule;
    }

    protected function resolve(array $data, array $rules, array $messages, array $customAttributes)
    {
        if (is_null($this->resolver)) {
            return new Validator($this->translator, $data, $rules, $messages, $customAttributes, $this->aliases);
        }

        return call_user_func($this->resolver, $this->translator, $data, $rules, $messages, $customAttributes, $this->aliases);
    }
}
