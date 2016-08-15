<?php

namespace Lthrt\CarveBundle\Traits\Controller;

use Lthrt\CarveBundle\Form\AssignType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

trait AssignFormTrait
{
    private function getAssignForm($count = 1)
    {
        $form = $this->createForm(AssignType::class, null, [
            'action' => $this->generateUrl('key'),
            'method' => 'POST',
            'count'  => $count,
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'Save']);

        return $form;
    }
}
