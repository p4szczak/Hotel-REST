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

        $reservation = new Reservation();
        $reservation->setClient($client);
        $reservation->setRoom($room);
        $reservation->setStartDate(date_create($data['start_date']));
        $reservation->setEndDate(date_create($data['end_date']));

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

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($reservation);
        $entityManager->flush();

        return new Response('Corectly added service '.$data['sid'].' to reservation '.$rid);
     }
}
