<?php

namespace MROC\AdminBundle\Form;

use MROC\MainBundle\Entity\User;
use MROC\MainBundle\Entity\UserRepository;
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
            ->add('user','entity',array(
                'class' => 'MROCMainBundle:User',
                'label' => 'Пользователь',
                'property' => 'username',
                'query_builder' => function(UserRepository $er) {
                    $qb = $er->createQueryBuilder('u');
                    return $qb->where($qb->expr()->like('u.roles',$qb->expr()->literal('%ROLE_PARTNER%')));
                }
            ))
            ->add('image', 'file',array(
                'label' => 'Фотография',
                'data_class' => null,
                'required' => false
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
