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
     * @SWG\Response(response=404, description="not found")
     * @SWG\Response(response=409, description="date conflict")
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
            return new Response('Client not found', Response::HTTP_NOT_FOUND, ['content-type' => 'text/html']);
        }

        $room = $this->getDoctrine()->getRepository(Room::class)->find($data['_room']);

        if (!$room) {
            return new Response('Room not found', Response::HTTP_NOT_FOUND, ['content-type' => 'text/html']);
        }

        if(!$room->getIsAvaiable()){
            return new Response('Room is unavailable!', Response::HTTP_CONFLICT, ['content-type' => 'text/html']);
        }
        $start = new \DateTime($data['start_date']);
        $end = new \DateTime($data['end_date']);

        if(!$this->canAddReservation($room, $start, $end)){
            return new Response('Date is overlapping another one!', Response::HTTP_CONFLICT, ['content-type' => 'text/html']);
        }
        
        $reservation = new Reservation();
        $reservation->setClient($client);
        $reservation->setRoom($room);
        $reservation->setStartDate($start);
        $reservation->setEndDate($end);

        if($reservation->getEndDate() <= $reservation->getStartDate()){
            return new Response('End date must be after start date!', Response::HTTP_CONFLICT, ['content-type' => 'text/html']);
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
            if($value->getRoom()->getId() != $room->getId()){
                continue;
            }

            //jezeli jest przed
            if($start < $value->getStartDate() and $end <= $value->getStartDate()){
                $isBefore = true;
            }
            //jezeli jest po 
            if($start >= $value->getEndDate() and $end > $value->getEndDate()){
                $isAfter = true;
            }

            if(!$isBefore and !$isAfter){
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
     * @SWG\Response(response=404, description="not found")
     * @SWG\Response(response=409, description="date conflict")
     * 
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
            return new Response('Service not found', Response::HTTP_NOT_FOUND, ['content-type' => 'text/html']);
        }

        if(!$service->getIsAvailable()){
            return new Response('You cannot add unavailable servieces to reservation!', Response::HTTP_CONFLICT, ['content-type' => 'text/html']);
        }
        $reservation = $this->getDoctrine()->getRepository(Reservation::class)->find($rid);
        if (!$reservation) {
            return new Response('Reservation not found', Response::HTTP_NOT_FOUND, ['content-type' => 'text/html']);
        }



        $today = new \DateTime();

        if ($reservation->getEndDate() <= $today){
            return new Response('You cannot add services to archived reservation!', Response::HTTP_CONFLICT, ['content-type' => 'text/html']);
        }

        if ($reservation->getServices()->contains($service)){
            return new Response('You cannot add this same service twice!', Response::HTTP_CONFLICT, ['content-type' => 'text/html']);    
        }
        $reservation->addService($service);

        
        $interval = $reservation->getEndDate()->diff($reservation->getStartDate());
        $oldCost = $reservation->getCost();
        $extraCost = $service->getCost() * $reservation->getRoom()->getPlacesCount() * $interval->d;
        $reservation->setCost($oldCost + $extraCost);

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
     * @SWG\Response(response=404, description="not found")
     * @SWG\Response(response=409, description="date conflict") 
     *
     * @param int $rid
     * @param int $id
     */

    public function removeServicefromReservation(int $rid, int $id){
        $reservation = $this->getDoctrine()->getRepository(Reservation::class)->find($rid);
        $entityManager = $this->getDoctrine()->getManager();

        if (!$reservation) {
            return new Response('Reservation not found', Response::HTTP_NOT_FOUND, ['content-type' => 'text/html']);
        }
        
        $today = new \DateTime();

        if ($reservation->getStartDate() <= $today){
            return new Response('You cannot delete services from archived reservation!', Response::HTTP_CONFLICT, ['content-type' => 'text/html']);
        }

        $service = $this->getDoctrine()->getRepository(Service::class)->find($id);

        if (!$service) {
            return new Response('Service not found', Response::HTTP_NOT_FOUND, ['content-type' => 'text/html']);
        }

        $interval = $reservation->getEndDate()->diff($reservation->getStartDate());
        $oldCost = $reservation->getCost();
        $extraCost = $service->getCost() * $reservation->getRoom()->getPlacesCount() * $interval->d;
        $reservation->setCost($oldCost - $extraCost);

        
        $reservation->removeService($service);
        $entityManager->flush();

        return new Response("Object with id: ".$id." has been removed from: ".$rid);
    }

    /**
     * @Route("/api/reservation/{rid}/service", name="list_services_in_reservation", methods={"GET"})
     * 
     * @SWG\Tag(name="reservation")
     * @SWG\Response(response=200, description="successful operation")
     * @SWG\Response(response=404, description="not found")
     * 
     * @param int $rid
     * 
     */
    public function listServicesInReservation(int $rid){

        $reservation = $this->getDoctrine()->getRepository(Reservation::class)->find($rid);

        if (!$reservation) {
            return new Response('Reservation not found', Response::HTTP_NOT_FOUND, ['content-type' => 'text/html']);
        }
        
        $arr = array();
        foreach ($reservation->getServices() as &$value) {
            $response = [
                "name" => $value->getName(),
                "cost" => $value->getCost(),
                "is_available" => $value->getIsAvailable()
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
     * @SWG\Response(response=404, description="not found")
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
                "end_date" => $value->getEndDate()->format('Y-m-d'),
                "cost" => $value->getCost(),
            ];
            array_push($arr, $response);
        }
        return new JsonResponse($arr);   
     }

     /**
     * @Route("/api/reservation/{id}", name="show_reservation", methods={"GET"})
     * 
     * @SWG\Tag(name="reservation")
     * @SWG\Response(response=200, description="successful operation")
     * @SWG\Response(response=404, description="not found")
     * 
     *  @param int $id
     * 
     */
     public function showReservation(int $id){
        $reservation = $this->getDoctrine()->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            return new Response('Reservation not found', Response::HTTP_NOT_FOUND, ['content-type' => 'text/html']);
        }
        
        $response = [
            "_room" => $reservation->getRoom()->getId(),
            "client" => $reservation->getClient()->getId(),
            "start_date" => $reservation->getStartDate()->format('Y-m-d'),
            "end_date" => $reservation->getEndDate()->format('Y-m-d'),
            "cost" => $reservation->getCost(),
        ];

        return new JsonResponse($response);     
     }

    /**
     * @Route("/api/reservation/{id}", name="delete_reservation", methods={"DELETE"})
     * 
     * @SWG\Tag(name="reservation")
     * @SWG\Response(response=200, description="successful operation")
     * @SWG\Response(response=404, description="not found")
     * @SWG\Response(response=409, description="date conflict")
     * 
     *  @param int $id
     * 
     */

    public function deleteReservation(int $id){

        $reservation = $this->getDoctrine()->getRepository(Reservation::class)->find($id);
        $entityManager = $this->getDoctrine()->getManager();

        if (!$reservation) {
            return new Response('Reservation not found', Response::HTTP_NOT_FOUND, ['content-type' => 'text/html']);
        }

        $today = new \DateTime();

        if ($reservation->getEndDate() <= $today){
            return new Response('You cannot delete archived reservation!', Response::HTTP_CONFLICT, ['content-type' => 'text/html']);
        }

        if($reservation->getStartDate() <= $today and $reservation->getEndDate() > $today){
            return new Response('You cannot delete began reservations!', Response::HTTP_CONFLICT, ['content-type' => 'text/html']);
        }
        
        $entityManager->remove($reservation);
        $entityManager->flush();

        return new Response("Object with id: ".$id." has been removed");
     }

}
