<?php

namespace MROC\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OwnerEditMessageType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('header','text',array(
                'label' => 'Заголовок'
            ))
            ->add('text','textarea',array(
                'label' => 'Сообщение'
            ))
            ->add('save', 'submit', array(
                'attr' => array('class' => 'button success'),
                'label' => 'Добавить'
            ));
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
        return 'mroc_adminbundle_owner_edit_message';
    }
}
