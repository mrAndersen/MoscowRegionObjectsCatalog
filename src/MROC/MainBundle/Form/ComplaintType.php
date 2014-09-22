<?php

namespace MROC\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ComplaintType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name','text',array('label'=>'Ваше имя'))
            ->add('email','text',array('label'=>'E-mail'))
            ->add('problem','textarea',array('label'=>'Проблема'))
            ->add('captcha', 'captcha',array(
                'invalid_message' => 'Неверная капча.'
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
            'data_class' => 'MROC\MainBundle\Entity\Complaint'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'mroc_mainbundle_complaint';
    }
}
