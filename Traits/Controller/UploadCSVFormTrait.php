<?php

namespace Lthrt\CarveBundle\Traits\Controller;

use Lthrt\CarveBundle\Form\UploadCSVType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

trait UploadCSVFormTrait
{
    private function getUploadForm()
    {
        $form = $this->createForm(UploadCSVType::class, null, [
            'action' => $this->generateUrl('assign'),
            'method' => 'POST',
        ]);
        $form->add('submit', SubmitType::class, ['label' => 'Save']);

        return $form;
    }
}
