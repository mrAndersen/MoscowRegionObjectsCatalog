<?php

namespace MROC\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class CommentType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id',null,array(
                'mapped' => false
            ))
            ->add('name','text',array(
                'label'=>'Ваше имя',
                'constraints' => array(
                    new NotBlank(array('message'=>'Поле с именем должно быть заполнено.')),
                ),
            ))
            ->add('email','text',array(
                'label'=>'E-mail',
                'constraints' => array(
                    new NotBlank(array('message'=>' Поле с электронной почтой должно быть заполнено.')),
                    new Email(array('message'=>'Указаный вами email - {{ value }} не является адресом электронной почты.'))
                ),
            ))
            ->add('comment','textarea',array(
                'label'=>'Комментарий',
                'constraints' => array(
                    new NotBlank(array('message'=>' Поле с комментарием должно быть заполнено.')),
                )
            ))
            ->add('captcha', 'captcha',array(
                'invalid_message' => 'Неверная капча.',
                'width' => '190'
            ))
            ->add('send', 'submit', array('label' => 'Отправить'))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'mroc_mainbundle_comment';
    }
}
