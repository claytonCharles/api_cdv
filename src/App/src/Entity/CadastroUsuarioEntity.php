<?php

namespace App\Entity;

use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\Form\Annotation;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Password;
use Laminas\Form\Element\Text;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\StringLength;

#[Annotation\Name("form-cadastro-usuario")]
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class CadastroUsuarioEntity
{
    #[Annotation\Type(Text::class)]
    #[Annotation\Filter(StringTrim::class)]
    #[Annotation\Filter(StripTags::class)]
    #[Annotation\Validator(StringLength::class, options: ["min" => "4"])]
    public $ds_nome;

    #[Annotation\Type(Email::class)]
    public $ds_email;
    
    #[Annotation\Type(Password::class)]
    #[Annotation\Filter(StringTrim::class)]
    #[Annotation\Filter(StripTags::class)]
    #[Annotation\Validator(StringLength::class, options: ["min" => "6"])]
    public $ds_senha;
}