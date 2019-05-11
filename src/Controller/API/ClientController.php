<?php

namespace App\Controller\API;


use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Acme\FooBundle\Validation\Constraints\MyComplexConstraint;

use Symfony\Component\HttpFoundation\{JsonResponse, Response, Request};
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ClientController extends AbstractController
{
    /**
     * @Route("/api/client", name="add_client", methods={"POST"})
     * 
     * @SWG\Tag(name="client")
     * @SWG\Response(response=200, description="successful operation")
     * 
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref=@Model(type=Client::class)),
     * )
     * 
     * @param Request $request
     * 
     */
    public function addClient(Request $request) {
        $data = json_decode($request->getContent(), true);
        if (!$request) {
            return $this->respondValidationError('Please provide a valid request!');
        }

        $client = new Client();
        $client->setFirstName($data['first_name']);
        $client->setLastName($data['last_name']);
        $client->setPhone($data['phone']);
        $client->setEmail($data['email']);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($client);
        $entityManager->flush();
        return new Response('Saved new client with id '.$client->getId());
     }



    /**
     * @Route("/api/client/{id}", name="show_client", methods={"GET"})
     * 
     * @SWG\Tag(name="client")
     * @SWG\Response(response=200, description="successful operation")
     * 
     *  @param int $id
     * 
     */
     public function showClient(int $id){
        $client = $this->getDoctrine()->getRepository(Client::class)->find($id);

        if (!$client) {
            throw $this->createNotFoundException('No client found for id '.$id);
        }
        
        $response = [
            "first_name" => $client->getFirstName(),
            "last_name" => $client->getLastName(),
            "phone" => $client->getPhone(),
            "email" => $client->getEmail(),
        ];

        return new JsonResponse(json_encode($response));     
     }


     /**
     * @Route("/api/client/", name="list_clients", methods={"GET"})
     * 
     * @SWG\Tag(name="client")
     * @SWG\Response(response=200, description="successful operation")
     * 
     *
     * 
     */
     public function listClients(){
        //TODO PAGES
        $clients = $this->getDoctrine()->getRepository(Client::class)->findAll();
        $arr = array();
        foreach ($clients as &$value) {
            $response = [
                "first_name" => $value->getFirstName(),
                "last_name" => $value->getLastName(),
                "phone" => $value->getPhone(),
                "email" => $value->getEmail(),
            ];
            array_push($arr, $response);
        }
        return new JsonResponse(json_encode($arr));   
     }

     /**
     * @Route("/api/client/{id}", name="delete_client", methods={"DELETE"})
     * 
     * @SWG\Tag(name="client")
     * @SWG\Response(response=200, description="successful operation")
     * 
     *  @param int $id
     * 
     */

     public function deleteClient(int $id){

        $client = $this->getDoctrine()->getRepository(Client::class)->find($id);
        $entityManager = $this->getDoctrine()->getManager();

        if (!$client) {
            throw $this->createNotFoundException('No client found for id '.$id);
        }
        
        $entityManager->remove($client);
        $entityManager->flush();

        return new Response("Object with id: ".$id." has been removed");
     }

     /**
     * @Route("/api/client/{id}", name="update_client", methods={"PUT"})
     * 
     * @SWG\Tag(name="client")
     * @SWG\Response(response=200, description="successful operation")
     * 
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref=@Model(type=Client::class)),
     * )
     * 
     * @param int $id
     * @param Request $request
     * 
     */

    public function updateClient(int $id, Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $entityManager = $this->getDoctrine()->getManager();
        $client = $entityManager->getRepository(Client::class)->find($id);
    
        if (!$client) {
            throw $this->createNotFoundException(
                'No client found for id '.$id
            );
        }
    
        $client->setFirstName($data['first_name']);
        $client->setLastName($data['last_name']);
        $client->setPhone($data['phone']);
        $client->setEmail($data['email']);
        $entityManager->flush();
    
        // return $this->redirectToRoute('show_client', [
        //     'id' => $client->getId()
        // ]);
        return new Response("Client with id '.$id.' updated successfully!");
    }
     
}