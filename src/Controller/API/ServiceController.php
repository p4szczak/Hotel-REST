<?php

namespace App\Controller\API;


use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Service;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Acme\FooBundle\Validation\Constraints\MyComplexConstraint;

use Symfony\Component\HttpFoundation\{JsonResponse, Response, Request};
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ServiceController extends AbstractController
{
    /**
     * @Route("/api/service", name="add_service", methods={"POST"})
     * 
     * @SWG\Tag(name="service")
     * @SWG\Response(response=200, description="successful operation")
     * 
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref=@Model(type=Service::class)),
     * )
     * 
     * @param Request $request
     * 
     */
    public function addService(Request $request) {
        $data = json_decode($request->getContent(), true);
        if (!$request) {
            return $this->respondValidationError('Please provide a valid request!');
        }

        $service = new Service();
        $service->setName($data['name']);
        $service->setCost($data['cost']);
        $service->setIsAvailable($data['is_available']);
        

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($service);
        $entityManager->flush();
        return new Response('Saved new service with id '.$service->getId());
     }

     /**
     * @Route("/api/service/{id}", name="show_service", methods={"GET"})
     * 
     * @SWG\Tag(name="service")
     * @SWG\Response(response=200, description="successful operation")
     * 
     *  @param int $id
     * 
     */
    public function showService(int $id){
        $service = $this->getDoctrine()->getRepository(Service::class)->find($id);

        if (!$service) {
            throw $this->createNotFoundException('No service found for id '.$id);
        }
        
        $response = [
            "name" => $service->getName(),
            "cost" => $service->getCost(),
            "is_available" => $service->getIsAvailable()
        ];

        return new JsonResponse(json_encode($response));     
     }

     /**
     * @Route("/api/service/", name="list_services", methods={"GET"})
     * 
     * @SWG\Tag(name="service")
     * @SWG\Response(response=200, description="successful operation")
     *
     * @param Request $request
     */
    public function listServices(Request $request){

        $services = $this->getDoctrine()->getRepository(Service::class)->findAll();
        $arr = array();
        foreach ($services as &$value) {
            $response = [
                "name" => $value->getName(),
                "cost" => $value->getCost(),
                "is_available" => $value->getIsAvailable(),
            ];
            array_push($arr, $response);
        }
        return new JsonResponse($arr);   
     }

     /**
     * @Route("/api/service/{id}", name="delete_service", methods={"DELETE"})
     * 
     * @SWG\Tag(name="service")
     * @SWG\Response(response=200, description="successful operation")
     * 
     *  @param int $id
     * 
     */

     public function deleteService(int $id){

        $service = $this->getDoctrine()->getRepository(Service::class)->find($id);
        $entityManager = $this->getDoctrine()->getManager();

        if (!$service) {
            throw $this->createNotFoundException('No service found for id '.$id);
        }
        
        $entityManager->remove($service);
        $entityManager->flush();

        return new Response("Object with id: ".$id." has been removed");
     }

     /**
     * @Route("/api/service/{id}", name="update_service", methods={"PUT"})
     * 
     * @SWG\Tag(name="service")
     * @SWG\Response(response=200, description="successful operation")
     * 
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref=@Model(type=Service::class)),
     * )
     * 
     * @param int $id
     * @param Request $request
     * 
     */

    public function updateService(int $id, Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $entityManager = $this->getDoctrine()->getManager();
        $service = $entityManager->getRepository(Service::class)->find($id);
    
        if (!$service) {
            throw $this->createNotFoundException(
                'No service found for id '.$id
            );
        }
    
        $service->setName($data['name']);
        $service->setCost($data['cost']);
        $service->setIsAvailable($data['is_available']);
        $entityManager->flush();
    
        // return $this->redirectToRoute('show_service', [
        //     'id' => $service->getId()
        // ]);
        return new Response("Service with id '.$id.' updated successfully!");
    }

    }