<?php

use Codeception\Specify;
use Illuminate\Support\Facades\Config;
use Jedrzej\Validation\Validator;
use Codeception\TestCase\Test;

class ValidationTest extends Test
{
    use Specify;

    protected function makeValidator($rules, $data, $aliases = [])
    {
        return new Validator(new TestTranslator, $data, $rules, [], [], $aliases);
    }

    public function testNegation()
    {
        $this->specify("validation status is negated if rule is prefixed with exclamation mark", function () {
            $rules = ['a' => 'min:3'];

            $data = ['a' => 'abcd'];
            $this->assertTrue($this->makeValidator($rules, $data)->passes());

            $data = ['a' => 'ab'];
            $this->assertFalse($this->makeValidator($rules, $data)->passes());

            $rules = ['a' => '!min:3'];

            $data = ['a' => 'abcd'];
            $this->assertFalse($this->makeValidator($rules, $data)->passes());

            $data = ['a' => 'ab'];
            $this->assertTrue($this->makeValidator($rules, $data)->passes());
        });
    }

    public function testPlaceholders()
    {
        $this->specify("placeholders are replaced with values of other validated fields", function () {
            $rules = ['a' => 'min:{{min}}'];

            $data = ['a' => 'abcd', 'min' => 3];
            $this->assertTrue($this->makeValidator($rules, $data)->passes());

            $data = ['a' => 'ab', 'min' => 3];
            $this->assertFalse($this->makeValidator($rules, $data)->passes());

            $rules = ['a' => '!min:{{min}}'];

            $data = ['a' => 'abcd', 'min' => 3];
            $this->assertFalse($this->makeValidator($rules, $data)->passes());

            $data = ['a' => 'ab', 'min' => 3];
            $this->assertTrue($this->makeValidator($rules, $data)->passes());
        });

        $this->specify("placeholders are replaced with config values", function () {
            Config::shouldReceive('get')->with('app.min')->andReturn(3);
            $rules = ['a' => 'min:{{app.min}}'];

            $data = ['a' => 'abcd'];
            $this->assertTrue($this->makeValidator($rules, $data)->passes());

            $data = ['a' => 'ab'];
            $this->assertFalse($this->makeValidator($rules, $data)->passes());

            $rules = ['a' => '!min:{{app.min}}'];

            $data = ['a' => 'abcd'];
            $this->assertFalse($this->makeValidator($rules, $data)->passes());

            $data = ['a' => 'ab'];
            $this->assertTrue($this->makeValidator($rules, $data)->passes());
        });
    }

    public function testAliases()
    {
        $this->specify("aliases to validation rules can be defined", function () {
            $aliases = ['Hex' => 'regex:/^[0-9a-fA-F]+$/'];
            $rules = ['a' => 'hex'];

            $data = ['a' => 'abcde'];
            $this->assertTrue($this->makeValidator($rules, $data, $aliases)->passes());

            $data = ['a' => 'abcdefg'];
            $this->assertFalse($this->makeValidator($rules, $data, $aliases)->passes());

            $rules = ['a' => '!hex'];

            $data = ['a' => 'abcde'];
            $this->assertFalse($this->makeValidator($rules, $data, $aliases)->passes());

            $data = ['a' => 'abcdefg'];
            $this->assertTrue($this->makeValidator($rules, $data, $aliases)->passes());
        });
    }
}
