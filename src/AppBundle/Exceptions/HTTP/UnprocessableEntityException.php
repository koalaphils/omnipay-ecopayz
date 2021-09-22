<?php


namespace AppBundle\Exceptions\HTTP;

class UnprocessableEntityException extends \Exception
{
    private $errors;

    public function __construct(array $errors, $code = 422, \Throwable $previous = null)
    {
        $this->errors = $errors;
        parent::__construct('Server respond with 422', $code, $previous);
    }


    public function getErrors()
    {
        return $this->errors;
    }


    public function __get($field)
    {
        if (array_key_exists($field, $this->errors) && count($this->errors[$field]) > 0) {
            return $this->errors[$field];
        }

        return '';
    }
}
