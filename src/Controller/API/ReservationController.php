<?php

namespace App\Controller\API;


use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Reservation;
use App\Entity\Client;
use App\Entity\Room;
use App\Entity\Service;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Acme\FooBundle\Validation\Constraints\MyComplexConstraint;

use Symfony\Component\HttpFoundation\{JsonResponse, Response, Request};
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class ReservationController extends AbstractController
{
    /**
     * @Route("/api/reservation", name="add_reservation", methods={"POST"})
     * 
     * @SWG\Tag(name="reservation")
     * @SWG\Response(response=200, description="successful operation")
     * 
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref=@Model(type=Reservation::class)),
     * )
     * 
     * @param Request $request
     * 
     */
    public function addReservation(Request $request) {
        $data = json_decode($request->getContent(), true);
        if (!$request) {
            return $this->respondValidationError('Please provide a valid request!');
        }

        $client = $this->getDoctrine()->getRepository(Client::class)->find($data['client']);
        if (!$client) {
            return $this->respondValidationError('Please provide a valid client!');
        }

        $room = $this->getDoctrine()->getRepository(Room::class)->find($data['_room']);

        if (!$room) {
            return $this->respondValidationError('Please provide a valid room!');
        }

        $start = new \DateTime($data['start_date']);
        $end = new \DateTime($data['end_date']);

        //TO_DO canAddReservation!
        // if(canAddReservation())

        $reservation = new Reservation();
        $reservation->setClient($client);
        $reservation->setRoom($room);
        $reservation->setStartDate($start);
        $reservation->setEndDate($end);

        if($reservation->getEndDate() <= $reservation->getStartDate()){
            return $this->respondValidationError('End date must be later than start date.. Please provide!');
        }

        $interval = $reservation->getEndDate()->diff($reservation->getStartDate());
        $days = $interval->d;

        

        
        $reservation->setCost($room->getCostPerDay() * $days);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($reservation);
        $entityManager->flush();
        return new Response('Saved new reservation with id '.$reservation->getId());
     }

     private function canAddReservation(Room $room, \DateTime $start, \DateTime $end){
        $reservations = $this->getDoctrine()->getRepository(Reservation::class)->findAll();

        foreach ($reservations as &$value){
            $isBefore = false;
            $isAfter = false;
            //jezeli pokoj nie jest ten sam to zacznij ponownie
            if($value->getRoomNumber() != $room->getRoomNumber()){
                continue;
            }

            //jezeli jest przed
            if($start < $value->getStartDate() and $end <= $value->getEndDate()){
                $isBefore = true;
            }
            //jezeli jest po 
            if($start >= $value->getStartDate() and $end > $value->getEndDate()){
                $isAfter = true;
            }

            if(!$isBefore and $isAfter){
                return false;
            }
            
        }
        
        return true;
     }


     /**
     * @Route("/api/reservation/{rid}/service/", name="add_service_to_reservation", methods={"POST"})
     * 
     * @SWG\Tag(name="reservation")
     * @SWG\Response(response=200, description="successful operation")
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(),
     * )
     * @param Request $request
     */

    public function addServiceToReservation(Request $request, int $rid) {

        
        $data = json_decode($request->getContent(), true);
        $service =$this->getDoctrine()->getRepository(Service::class)->find($data['sid']);
        if (!$service) {
            throw $this->createNotFoundException('No service found for id '.$data['sid']);
        }
        $reservation = $this->getDoctrine()->getRepository(Reservation::class)->find($rid);
        if (!$reservation) {
            throw $this->createNotFoundException('No service found for id '.$rid);
        }

        $reservation->addService($service);

        $oldCost = $reservation->getCost();
        $reservation->setCost($oldCost + $service->getCost());

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($reservation);
        $entityManager->flush();

        return new Response('Corectly added service '.$data['sid'].' to reservation '.$rid);
     }

     /**
     * @Route("/api/reservation/{rid}/service/{id}", name="remove_service_from_reservation", methods={"DELETE"})
     * 
     * @SWG\Tag(name="reservation")
     * @SWG\Response(response=200, description="successful operation")
     * 
     *
     * @param int $rid
     * @param int $id
     */

    public function removeServicefromReservation(int $rid, int $id){
        $reservation = $this->getDoctrine()->getRepository(Reservation::class)->find($rid);
        $entityManager = $this->getDoctrine()->getManager();

        if (!$reservation) {
            throw $this->createNotFoundException('No reservation found for id '.$rid);
        }
        
        $service = $this->getDoctrine()->getRepository(Service::class)->find($id);

        if (!$service) {
            throw $this->createNotFoundException('No service found for id '.$id);
        }

        
        $reservation->removeService($service);
        $entityManager->flush();

        return new Response("Object with id: ".$id." has been removed from: ".$rid);
    }

    /**
     * @Route("/api/reservation/{rid}/service", name="list_services_in_reservation", methods={"GET"})
     * 
     * @SWG\Tag(name="reservation")
     * @SWG\Response(response=200, description="successful operation")
     * @param int $rid
     * 
     */
    public function listServicesInReservation(int $rid){

        $reservation = $this->getDoctrine()->getRepository(Reservation::class)->find($rid);
        
        $arr = array();
        foreach ($reservation->getServices() as &$value) {
            $response = [
                "name" => $value->getName(),
                "cost" => $value->getCost(),
            ];
            array_push($arr, $response);
        }
        return new JsonResponse($arr);   
    }



    /**
     * @Route("/api/reservation/", name="list_reservations", methods={"GET"})
     * 
     * @SWG\Tag(name="reservation")
     * @SWG\Response(response=200, description="successful operation")
     * @param Request $request
     */
    public function listReservations(Request $request){

        $reservations = $this->getDoctrine()->getRepository(Reservation::class)->findAll();
        $arr = array();
        foreach ($reservations as &$value) {
            $response = [
                "_room" => $value->getRoom()->getId(),
                "client" => $value->getClient()->getId(),
                "start_date" => $value->getStartDate()->format('Y-m-d'),
                "end_date" => $value->getEndDate(),
                "cost" => $value->getCost(),
            ];
            array_push($arr, $response);
        }
        return new JsonResponse(array_slice($arr, $page * $pageSize, $pageSize));   
     }

     /**
     * @Route("/api/reservation/{id}", name="show_reservation", methods={"GET"})
     * 
     * @SWG\Tag(name="reservation")
     * @SWG\Response(response=200, description="successful operation")
     * 
     *  @param int $id
     * 
     */
     public function showReservation(int $id){
        $reservation = $this->getDoctrine()->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('No reservation found for id '.$id);
        }
        
        $response = [
            "_room" => $reservation->getRoom()->getId(),
            "client" => $reservation->getClient()->getId(),
            "start_date" => $reservation->getStartDate()->format('Y-m-d'),
            "end_date" => $reservation->getEndDate(),
            "cost" => $reservation->getCost(),
        ];

        return new JsonResponse(json_encode($response));     
     }

    /**
     * @Route("/api/reservation/{id}", name="delete_reservation", methods={"DELETE"})
     * 
     * @SWG\Tag(name="reservation")
     * @SWG\Response(response=200, description="successful operation")
     * 
     *  @param int $id
     * 
     */

    public function deleteReservation(int $id){

        $reservation = $this->getDoctrine()->getRepository(Reservation::class)->find($id);
        $entityManager = $this->getDoctrine()->getManager();

        if (!$reservation) {
            throw $this->createNotFoundException('No reservation found for id '.$id);
        }

        $today = new \DateTime();

        if ($reservation->getEndDate() <= $today){
            return new Response("You cannot delete archived reservations");
        }

        if($reservation->getStartDate() <= $today and $reservation->getEndDate() > $today){
            return new Response("You cannot delete began reservations");
        }
        
        $entityManager->remove($reservation);
        $entityManager->flush();

        return new Response("Object with id: ".$id." has been removed");
     }
}
