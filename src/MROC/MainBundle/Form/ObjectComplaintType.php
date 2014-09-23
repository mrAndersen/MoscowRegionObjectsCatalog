<?php

namespace MROC\MainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ObjectComplaintType extends AbstractType
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
            ->add('name','text')
            ->add('tel','text')
            ->add('email','text')
            ->add('problem','textarea')
            ->add('image', 'file',array(
                'label' => 'Фотография',
                'data_class' => null,
                'required' => true
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
            'data_class' => 'MROC\MainBundle\Entity\ObjectComplaint'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'mroc_mainbundle_objectcomplaint';
    }
}
