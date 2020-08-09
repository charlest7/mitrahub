<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\CommonSimpleService;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\DatahubRepository;
use App\Entity\Datahub;
use App\Repository\UploadRepository;
use App\Entity\Upload;
use Symfony\Component\HttpFoundation\Session\Session;



/**
* @Route("/datahub")
*/
class DatahubController extends AbstractController
{
    /**
     * @Route("/", name="datahub_index")
     */
    public function index(UploadRepository $uploadRepository)
    {
        $session = new Session();

        $uploads = $uploadRepository->findBy(['userId'=>$session->get('userId')]);
        return $this->render('datahub/index.html.twig', [
            'controller_name' => 'DatahubController',
            'uploads' => $uploads
        ]);
    }

    /**
     * @Route("/list", name="datahub_list")
     */
    public function list(Request $request,DatahubRepository $datahubRepository)
    {
        $uploadId = $request->query->get('uploadId');

        $headers = $datahubRepository->findBy(['uploadId' => $uploadId, 'type' => 'header']);
        $datahubs = $datahubRepository->findBy(['uploadId' => $uploadId, 'type' => 'field']);

        return $this->render('datahub/upload.html.twig', [
            'controller_name' => 'DatahubController',
            'datahubs' => $datahubs,
            'headers' => $headers
        ]);
    }

    /**
     * @Route("/upload", name="datahub_upload")
     */
    public function uploadAction(Request $request, DatahubRepository $datahubRepository, UploadRepository $uploadRepository)
    {
        $session = new Session();
        $upload = $request->query->get('upload');
        $uploadId = $request->query->get('uniqueId');
        $statusBatch = $request->query->get('statusBatch');
        $type = $request->query->get('type');
        $totalData = $request->query->get('totalData');


        for($x=0;$x<count($upload);$x++){
            $lArray = 10-count($upload[$x]);
            $cArray = $upload[$x];
            for($y=0;$y<$lArray;$y++){
                array_push($cArray, "");
            }
            $datahubRepository->saveDatahub($cArray,$type,$uploadId);
        }

        $resultStatus = true;
        if($statusBatch == 1){
            $uploadRepository->saveUpload($totalData, $uploadId, $session->get('userId'));
            $resultStatus = false;
        }

        return new JsonResponse(array('message' => $resultStatus), 200);
    }
}
