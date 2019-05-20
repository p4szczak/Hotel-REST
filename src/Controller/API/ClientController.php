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
     * @SWG\Response(response=404, description="not found")
     * @SWG\Response(response=409, description="POST method has been duplicated")
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

        $client = $this->getDoctrine()->getRepository(Client::class)->findOneBy([
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'city' => $data['city'],
            'birthDate' => $data['birth_date']
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
     * @SWG\Response(response=404, description="not found")
     * @SWG\Response(response=304, description="not modified")
     * 
     *  @param int $id
     *  @param Request $request
     */
     public function showClient(int $id, Request $request){
        $client = $this->getDoctrine()->getRepository(Client::class)->find($id);

        if (!$client) {
            return new Response('Client not found', Response::HTTP_NOT_FOUND, ['content-type' => 'text/html']);
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
        if (!$client->getReservation()->isEmpty()){
            return new Response('You cannot delete client with reservations!', Response::HTTP_CONFLICT, ['content-type' => 'text/html']);
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
     * @SWG\Response(response=418, description="precondition failed")
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
    
        return new Response("Client with id '.$id.' updated successfully!");
    }


    /**
     * @Route("/api/client/{id}", name="patch_client", methods={"PATCH"})
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
     * 
     * @param int $id
     * @param Request $request
     * 
     */

    public function patchClient(int $id, Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $entityManager = $this->getDoctrine()->getManager();
        $client = $entityManager->getRepository(Client::class)->find($id);
    
        if (!$client) {
            throw $this->createNotFoundException(
                'No client found for id '.$id
            );
        }
    
        if(key_exists('first_name', $data)) $client->setFirstName($data['first_name']);
        if(key_exists('last_name', $data)) $client->setLastName($data['last_name']);
        if(key_exists('phone',$data)) $client->setPhone($data['phone']);
        if(key_exists('email',$data)) $client->setEmail($data['email']);
        if(key_exists('city',$data)) $client->setCity($data['city']);
        if(key_exists('birth_date',$data)) $client->setBirthDate($data['birth_date']);
        $entityManager->flush();
    
        return new Response("Client with id '.$id.' has been patched successfully!");
    }

    /**
     * @Route("/api/client/{cid}/reservation", name="list_reservations_of_client", methods={"GET"})
     * 
     * @SWG\Tag(name="client")
     * @SWG\Response(response=200, description="successful operation")
     * @SWG\Response(response=404, description="not found")
     * 
     * @param int $cid
     * 
     */
    public function listReservationsOfClient(int $cid){

        $client = $this->getDoctrine()->getRepository(Client::class)->find($cid);

        if (!$client) {
            return new Response('Client not found', Response::HTTP_NOT_FOUND, ['content-type' => 'text/html']);
        }
        
        $arr = array();
        foreach ($client->getReservation() as &$value) {
            $response = [
                "id" => $value->getId(),
                "_room" => $value->getRoom()->getId(),
                "client" => $value->getClient()->getId(),
                "start_date" => $value->getStartDate()->format('Y-m-d'),
                "end_date" => $value->getEndDate()->format('Y-m-d'),
                "cost" => $value->getCost()
            ];
            array_push($arr, $response);
        }
        return new JsonResponse($arr);   
    }
     
}