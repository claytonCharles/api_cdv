<?php

namespace App\Entity;

use Laminas\Filter\StringTrim;
use Laminas\Form\Annotation;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Password;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\StringLength;

#[Annotation\Name("form-autenticacao")]
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class AutenticarEntity
{
    #[Annotation\Type(Email::class)]
    public $email;
    
    #[Annotation\Type(Password::class)]
    #[Annotation\Filter(StringTrim::class)]
    #[Annotation\Validator(StringLength::class, options: ["min" => "6"])]
    public $password;
}