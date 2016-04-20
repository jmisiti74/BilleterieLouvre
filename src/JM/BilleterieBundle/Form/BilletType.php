<?php

namespace JM\BilleterieBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
class BilletType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', 'entity', array(
                'class'    => 'JMBilleterieBundle:Type',
                'property' => 'name',
                'multiple' => false
            ))
            ->add('dateReservation', 'date',array(
                'widget' =>'single_text', 
            ))
            ->add('tarifReduit')
            ->add('nom')
            ->add('prenom')
            ->add('pays', CountryType::class)
            ->add('email')
            ->add('dateNaissance', 'date',array( 'widget' =>'single_text', ))
            ->add('Enregistrer', 'submit')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JM\BilleterieBundle\Entity\Billet'
        ));
    }
}