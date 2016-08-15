<?php

namespace Lthrt\CarveBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CarveController extends Controller
{
    use \Lthrt\CarveBundle\Traits\Controller\UploadCSVFormTrait;
    use \Lthrt\CarveBundle\Traits\Controller\AssignFormTrait;

    /**
     * @Route("/key", name="key")
     */
    public function keyAction(Request $request)
    {
        // get carving for rest
        $maker = $this->get('lthrt_carve.entity_maker');
        $maker->makeTable($request->request->get('assign'));
        $file = $this->getParameter('temp_filestore') . '/carving.csv';

        $maker->makeRecords($request->request->get('assign'), $file);

        return $this->render(
            'LthrtCarveBundle:Carve:key.html.twig',
            [
            ]
        );
    }

    /**
     * @Route("/assign", name="assign")
     */
    public function assignAction(Request $request)
    {
        $upload = $this->getUploadForm();
        $upload->handleRequest($request);

        $count  = 0;
        $limit  = 20;
        $file   = $request->files->get('upload_csv')['csv'];
        $handle = fopen($file->getRealPath(), 'r');
        $data   = [];
        while (($data[] = fgetcsv($handle)) && $count < $limit) {
        }

        $fileName = 'carving.csv';
        $file->move(
            $this->getParameter('temp_filestore'),
            $fileName
        );

        $length = count($data[0]);

        $form = $this->getAssignForm($length)->createView();

        return $this->render(
            'LthrtCarveBundle:Carve:assign.html.twig',
            [
                'data' => $data,
                'form' => $form,
            ]
        );
    }

    /**
     * @Route("/upload", name="upload")
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
