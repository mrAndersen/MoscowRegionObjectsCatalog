<?php

namespace MROC\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ObjectType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('address','text',array(
                'label' => 'Адрес'
            ))
            ->add('owner','text',array(
                'label' => 'Владелец'
            ))
            ->add('object_type','entity',array(
                'class' => 'MROCMainBundle:ObjectType',
                'label' => 'Тип объекта',
                'property' => 'name'
            ))
            ->add('sale_type','entity',array(
                'class' => 'MROCMainBundle:SaleType',
                'label' => 'Тип продукции',
                'property' => 'name'
            ))
            ->add('image', 'file',array(
                'label' => 'Фотография'
            ))
            ->add('override','checkbox',array(
                'label' => 'Генерировать координаты из фотографии',
                'required' => false
            ))
            ->add('save', 'submit', array(
                'attr' => array('class' => 'button success submit'),
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
            'data_class' => 'MROC\MainBundle\Entity\Object'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'mroc_mainbundle_object';
    }
}
