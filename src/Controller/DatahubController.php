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
use App\Service\ImageUploader;
use Aws\S3\S3Client;

// require 'vendor/autoload.php';
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

        foreach ($datahubs as $datahub)
        {
            $dataType = $datahub->getDataType();
            $imageName = $datahub->getColumn1();
        }
        
        if ($datahub->getDataType() == 'image')
        {
            $imageName = $datahub->getColumn1();
            $imageUrl = $request->getSchemeAndHttpHost() .'/assets/images/' . $imageName;
            $datahub->setColumn1('<img src="' . $imageUrl . '">');
            $datahubs = [];
            array_push($datahubs, $datahub);
            
        }
        else
        {
            $datahubs = $datahubRepository->findBy(['uploadId' => $uploadId, 'type' => 'field']);
        }

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
        $dataType = $request->query->get('dataType');
        $totalData = $request->query->get('totalData');
        // die(print($type));


        for($x=0;$x<count($upload);$x++){
            $lArray = 10-count($upload[$x]);
            $cArray = $upload[$x];
            for($y=0;$y<$lArray;$y++){
                array_push($cArray, "");
            }
            $datahubRepository->saveDatahub($cArray, $type, $dataType, $uploadId);
        }

        $resultStatus = true;
        if($statusBatch == 1){
            $uploadRepository->saveUpload($totalData, $uploadId, $session->get('userId'));
            $resultStatus = false;
        }

        return new JsonResponse(array('message' => $resultStatus), 200);
    }

    /**
     * @Route("/uploadImg", name="datahub_image")
     */
    public function uploadImgAction(Request $request, ImageUploader $imageUploader, string $uploadDir, UploadRepository $uploadRepository, DatahubRepository $datahubRepository)
    {
        $session = new Session();
        $file = $request->files->get('files');
        
        if (empty($file))
        {
            return new Response("No file specified",
            Response::HTTP_UNPROCESSABLE_ENTITY, ['content-type' => 'text/plain']);
        }
        $extension = explode('.', $_FILES['files']["name"]);
        $extension = strtolower(end($extension));
        $_FILES['files']["name"] = uniqid() . '.' . $extension;
        $filename = $_FILES['files']["name"];
        $tmpPath = $_FILES['files']["tmp_name"];

		$s3 = new S3Client([
			'region'  => 'ap-southeast-1',
			'version' => 'latest',
			'credentials' => [
				'key'    => "AKIAW5SXAGDG4V23NG2E",
				'secret' => "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",
			]
		]);		

		$result = $s3->putObject([
			'Bucket' => 'mitrahub',
            'Key'    => basename($filename),
            'SourceFile' => $tmpPath,
            'ACL'        => 'public-read'		
        ]);

        $imgUrl = $result->get('ObjectURL');

        $upload = array(
            array($imgUrl)
        );
        $uploadId = rand(100000, 1000000);
        $statusBatch = 1;
        $totalData = 1;

        for ($x=0;$x<count($upload);$x++)
        {
            $lArray = 10-count($upload[$x]);
            $cArray = $upload[$x];
            for($y=0;$y<$lArray;$y++)
            {
                array_push($cArray, "");
            }
            $datahubRepository->saveDatahub($cArray, 'field', 'image', $uploadId);

        }
            
        $resultStatus = true;
        if ($statusBatch == 1)
        {
            $uploadRepository->saveUpload($totalData, $uploadId, $session->get('userId'));
            $resultStatus = false;
        }

        return $this->redirectToRoute('datahub_index');
    }
}
