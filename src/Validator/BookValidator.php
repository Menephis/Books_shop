<?php
namespace src\Validator;

use Menephis\MaskValidator\Validator\Validator;

class BookValidator extends Validator
{
    /**
    * Validate latin + cyrillic + symbol + numeric
    *
    * @param string $text 
    * @param array $arguments - min, max lenght of string 
    *
    * @return $text - or false on failure
    */
    public function latinCyrillicLongText(string $text, array $arguments)
    {
        $pattern = sprintf('/^[\p{Latin}а-яА-ЯЁё"1-9<>.,`~\\\\@#!?<>()\[\]{}|*-+=&^:;%%\t\n\r ]{%d,%d}$/u', $arguments[0], $arguments[1]);
        return preg_match($pattern, $text) ? $text : false;   
    }
    /**
    * Validate latin + cyrillic + numeric
    *
    * @param string $text 
    * @param array $arguments - min, max lenght of string 
    *
    * @return $text - or false on failure
    */
    public function latinCyrillicText(string $text, array $arguments)
    {
        
        $pattern = sprintf('/^[\p{Latin}а-яА-ЯЁё"1-9.\-: ]{%d,%d}$/u', $arguments[0], $arguments[1]);
        return preg_match($pattern, $text) ? $text : false;   
    }
    /**
    * Year ranged
    *
    * @param int $year
    * @param array $arguments
    *
    * @return $year or false 
    */
    public function yearRanged($year, array $arguments)
    {
        $min = $arguments[0];
        $max = $arguments[1] ?? date('Y');
        if( $year >= $min and $year <= $max ) 
            return $year;
        return false;
    }
}
?>