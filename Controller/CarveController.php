<?php

namespace Lthrt\CarveBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CarveController extends Controller
{
    use \Lthrt\CarveBundle\Traits\Controller\UploadCSVFormTrait;

    /**
     * @Route("/review", name="review")
     */
    public function reviewAction(Request $request)
    {
        $form = $this->getUploadForm();
        $form->handleRequest($request);

        $file   = $request->files->get('upload_csv')['csv'];
        $handle = fopen($file->getRealPath(), 'r');
        while ($data = fgetcsv($handle)) {
            var_dump($data);
        }
        var_dump("Done");

        return $this->render(
            'LthrtCarveBundle:Carve:review.html.twig',
            [
            ]
        );
    }

    /**
     * @Route("/", name="upload")
     */
    public function uploadAction(Request $request)
    {
        $form = $this->getUploadForm();

        return $this->render(
            'LthrtCarveBundle:Carve:upload.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
