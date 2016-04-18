<?php

namespace JM\BilleterieBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('dateReservation', 'date',array('required' => false,
                                                      'widget' =>'single_text',
                                                      'format' =>'dd/MM/yyyy'))
            ->add('tarifReduit')
            ->add('nom')
            ->add('prenom')
            ->add('pays')
            ->add('email')
            ->add('dateNaissance', 'date')
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