<?php

namespace Lthrt\CarveBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssignType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(
        FormBuilderInterface $builder,
        array                $options
    ) {
        if (isset($options['count'])) {
            foreach (range(0, $options['count'] - 1) as $element) {
                $builder->add('class' . $element, TextType::class);
                $builder->add('field' . $element, TextType::class);
                $builder->add('type' . $element, ChoiceType::class,
                    [
                        'choices'     =>
                        [
                            'date'     => 'date',
                            'datetime' => 'datetime',
                            'float'    => 'float',
                            'integer'  => 'integer',
                            'key'      => 'key',
                            'string'   => 'string',
                            'text'     => 'text',
                        ],
                        'empty_data'  => null,
                        'placeholder' => '',
                    ]
                );
                $builder->add('key' . $element, ChoiceType::class,
                    [
                        'choices'     => range(0, $options['count'] - 1),
                        'empty_data'  => null,
                        'placeholder' => '',
                        'required'    => false,
                    ]
                );
            }
        }

    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'count' => 1,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'assign';
    }
}
