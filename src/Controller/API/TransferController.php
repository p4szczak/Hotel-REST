<?php

namespace App\Controller\API;


use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Transfer;
use App\Entity\Client;
use App\Entity\Room;
use App\Entity\Service;
use App\Entity\Reservation;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Acme\FooBundle\Validation\Constraints\MyComplexConstraint;

use Symfony\Component\HttpFoundation\{JsonResponse, Response, Request};
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class TransferController extends AbstractController
{
    /**
     * @Route("/api/transfer", name="add_transfer", methods={"POST"})
     * 
     * @SWG\Tag(name="transfer")
     * @SWG\Response(response=200, description="successful operation")
     * @SWG\Response(response=404, description="not found")
     * @SWG\Response(response=409, description="date conflict")
     * 
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref=@Model(type=Transfer::class)),
     * )
     * 
     * @param Request $request
     * 
     */
    public function addTransfer(Request $request) {
        $data = json_decode($request->getContent(), true);
        if (!$request) {
            return $this->respondValidationError('Please provide a valid request!');
        }

        $client = $this->getDoctrine()->getRepository(Client::class)->find($data['client']);
        if (!$client) {
            return new Response('Client not found', Response::HTTP_NOT_FOUND, ['content-type' => 'text/html']);
        }

        $reservation = $this->getDoctrine()->getRepository(Reservation::class)->find($data['reservation']);

        if (!$reservation) {
            return new Response('Reservation not found', Response::HTTP_NOT_FOUND, ['content-type' => 'text/html']);
        }

        $today = new \DateTime();

        if ($reservation->getEndDate() <= $today){
            return new Response("You cannot make transfer for archived reservations", Response::HTTP_CONFLICT, ['content-type' => 'text/html']); 
        }


        $reservation->setClient($client);

        $transfer = new Transfer();
        $transfer->setReservation($reservation);
        $transfer->setClient($client);


       
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($transfer);
        $entityManager->persist($reservation);
        $entityManager->flush();
        return new Response('Changed owner of reservation '.$reservation->getId().' to: '.$reservation->getClient()->getId());
     }


      /**
     * @Route("/api/transfer/{id}", name="transfer", methods={"GET"})
     * 
     * @SWG\Tag(name="transfer")
     * @SWG\Response(response=200, description="successful operation")
     * @SWG\Response(response=404, description="not found")
     *  @param int $id
     * 
     */
    public function showTransfer(int $id){
        $transfer = $this->getDoctrine()->getRepository(Transfer::class)->find($id);

        if (!$transfer) {
            return new Response('Transfer not found', Response::HTTP_NOT_FOUND, ['content-type' => 'text/html']);
        }
        
        $response = [
            "reservation" => $transfer->getReservation()->getId(),
            "client" => $transfer->getClient()->getId()
        ];

        return new JsonResponse($response);     
     }


      /**
     * @Route("/api/transfer/", name="list_transfers", methods={"GET"})
     * 
     * @SWG\Tag(name="transfer")
     * @SWG\Response(response=200, description="successful operation")
     *
     * @param Request $request
     */
    public function listTransfers(Request $request){

        $transfers = $this->getDoctrine()->getRepository(Transfer::class)->findAll();
        $arr = array();
        foreach ($transfers as &$value) {
            $response = [
                "reservation" => $value->getReservation()->getId(),
                "client" => $value->getClient()->getId(),
            ];
            array_push($arr, $response);
        }
        return new JsonResponse($arr);   
     }
}