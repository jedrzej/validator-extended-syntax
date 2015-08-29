<?php

use Codeception\Specify;
use Jedrzej\Validation\Validator;
use Codeception\TestCase\Test;

class ValidationTest extends Test
{
    use Specify;

    protected function makeValidator($rules, $data) {
        return new Validator(new TestTranslator, $data, $rules);
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
}
