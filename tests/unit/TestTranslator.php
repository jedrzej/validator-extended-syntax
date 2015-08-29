<?php

use Symfony\Component\Translation\TranslatorInterface;

class TestTranslator implements TranslatorInterface {
    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        //
    }

    public function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null)
    {
        //
    }

    public function setLocale($locale)
    {
        //
    }

    public function getLocale()
    {
        //
    }
}