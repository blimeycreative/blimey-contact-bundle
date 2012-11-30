<?php

namespace Savvy\ContactBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'choice', array(
                //An array of choices the user can pic
                'choices' => array(
                    'mr' => 'Mr',
                    'mrs' => 'Mrs',
                    'ms' => 'Ms',
                    'miss' => 'Miss',
                    'dr' => 'DR'
                    ),
                //Set an empty value so the user doesnt accidently submit "mr" without checking
                'empty_value' => 'Please select'
                ))
            ->add('name')
            ->add('company','text', array(
                //Stop HTML5 required validation
                'required'=>false
                ))
            ->add('telephone','text', array(
                'required'=>false
                ))
            ->add('email')
            ->add('message', 'textarea', array(
                //attr lets us set any key => value pair as a html attribute
                "attr" => array("cols" => "60", "rows" => 4) 
                ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Savvy\ContactBundle\Entity\Contact'
        ));
    }

    public function getName()
    {
        return 'savvy_contactbundle_contacttype';
    }
}
