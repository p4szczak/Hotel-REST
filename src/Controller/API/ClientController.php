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

        $client = $this->getDoctrine()->getRepository(Clinet::class)->findOneBy([
            "first_name" => $data['first_name'],
            "last_name" => $data['last_name'],
            "phone" => $data['phone'],
            "email" => $data['email'],
            "city" => $data['city'],
            'birth_date' => $data['birth_date']
        ]);

        if($client){
            //klient juz istnieje wiec ktos probuje drugi raz
            return new JsonResponse($data, JsonResponse::HTTP_CONFLICT, ['content-type' => 'application/json']);
        }

        $client = new Client();
        $client->setFirstName($data['first_name']);
        $client->setLastName($data['last_name']);
        $client->setPhone($data['phone']);
        $client->setEmail($data['email']);
        $client->setCity($data['city']);
        $client->setBirthDate($data['birth_date']);

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
     *  @param Request $request
     */
     public function showClient(int $id, Request $request){
        $client = $this->getDoctrine()->getRepository(Client::class)->find($id);

        if (!$client) {
            throw $this->createNotFoundException('No client found for id '.$id);
        }
        
        $data = [
            "first_name" => $client->getFirstName(),
            "last_name" => $client->getLastName(),
            "phone" => $client->getPhone(),
            "email" => $client->getEmail(),
            "city" => $client->getCity(),
            "birth_date" => $client->getBirthDate()
        ];

        $response = new Response();
        $response->setContent(json_encode($data));
        $response->headers->set('Content-Type', 'application/json');
        $response->setEtag(md5( $data['first_name'].
                                $data['last_name'].
                                $data['phone'].
                                $data['email'].
                                $data['city'].
                                $data['birth_date']
                            ));
        $response->setPublic();

        if ($response->isNotModified($request)) {
            return new Response('', Response::HTTP_NOT_MODIFIED, ['content-type' => 'text/html']);
        }

        return $response;     
     }


     /**
     * @Route("/api/client/", name="list_clients", methods={"GET"})
     * 
     * @SWG\Tag(name="client")
     * @SWG\Response(response=200, description="successful operation")
     * 
     * 
     * @SWG\Parameter(name="page", in="query", type="integer")
     * @SWG\Parameter(name="pageSize", in="query", type="integer") 
     *
     * @param Request $request
     */
     public function listClients(Request $request){

        $page =  $request->query->get('page');
        $pageSize =  $request->query->get('pageSize');

        $clients = $this->getDoctrine()->getRepository(Client::class)->findAll();
        $arr = array();
        foreach ($clients as &$value) {
            $response = [
                "first_name" => $value->getFirstName(),
                "last_name" => $value->getLastName(),
                "phone" => $value->getPhone(),
                "email" => $value->getEmail(),
                "city" => $value->getCity(),
                "birth_date" => $value->getBirthDate()
            ];
            array_push($arr, $response);
        }
        return new JsonResponse(array_slice($arr, $page * $pageSize, $pageSize));   
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
     * 
     * @SWG\Parameter(
     *      name="etag",
     *      in="header",
     *      required=true,
     *      type="string",
     * )
     * 
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

        $calculatedEtag = md5(  $client->getFirstName().
                                $client->getLastName().
                                $client->getPhone().
                                $client->getEmail().
                                $client->getCity().
                                $client->getBirthDate()
        );

        $recvEtag = $request->headers->get('etag');

        if ($calculatedEtag != $recvEtag) {
            return new Response('', Response::HTTP_PRECONDITION_FAILED, ['content-type' => 'text/html']);
        }
    
        $client->setFirstName($data['first_name']);
        $client->setLastName($data['last_name']);
        $client->setPhone($data['phone']);
        $client->setEmail($data['email']);
        $client->setCity($data['city']);
        $client->setBirthDate($data['birth_date']);
        $entityManager->flush();
    
        // return $this->redirectToRoute('show_client', [
        //     'id' => $client->getId()
        // ]);
        return new Response("Client with id '.$id.' updated successfully!");
    }
     
}