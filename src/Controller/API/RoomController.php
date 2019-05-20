<?php

namespace App\Controller\API;


use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Room;
use App\Entity\Reservation;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Acme\FooBundle\Validation\Constraints\MyComplexConstraint;

use Symfony\Component\HttpFoundation\{JsonResponse, Response, Request};
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class RoomController extends AbstractController
{
     /**
     * @Route("/api/room/", name="list_rooms", methods={"GET"})
     * 
     * @SWG\Tag(name="room")
     * @SWG\Response(response=200, description="successful operation")
     * @param Request $request
     */
    public function listRooms(Request $request){


        $rooms = $this->getDoctrine()->getRepository(Room::class)->findAll();
        $arr = array();
        foreach ($rooms as &$value) {
            $response = [
                "room_number" => $value->getRoomNumber(),
                "places_count" => $value->getPlacesCount(),
                "cost_per_day" => $value->getCostPerDay(),
                "type" => $value->getType(),
                "is_avaiable" => $value->getIsAvaiable(),
            ];
            array_push($arr, $response);
        }
        return new JsonResponse($arr);   
     }
    

     /**
     * @Route("/api/room/available", name="list_available_rooms", methods={"GET"})
     * 
     * @SWG\Tag(name="room")
     * @SWG\Response(response=200, description="successful operation")
     * @SWG\Response(response=409, description="date conflict")
     * 
     * @param Request $request
     * @SWG\Parameter(name="startDate", in="query", type="string")
     * @SWG\Parameter(name="endDate", in="query", type="string")
     */
    public function listAvaiableRooms(Request $request){

        $_startDate = new \Datetime($request->query->get('startDate'));
        $_endDate = new \Datetime($request->query->get('endDate'));
        if($_endDate <= $_startDate){
            return new Response('End date must be after start date!', Response::HTTP_CONFLICT, ['content-type' => 'text/html']);
        }
        $rooms = $this->getDoctrine()->getRepository(Room::class)->findAll();
        $arr = array();
        foreach ($rooms as &$value) {
            if(!$value->getIsAvaiable()) continue;
            if(!$this->isRoomAvailable($value, $_startDate, $_endDate, $value->getReservations())) continue;
            $response = [
                "room_number" => $value->getRoomNumber(),
                "places_count" => $value->getPlacesCount(),
                "cost_per_day" => $value->getCostPerDay(),
                "type" => $value->getType(),
                "is_avaiable" => $value->getIsAvaiable(),
            ];
            array_push($arr, $response);
        }
        return new JsonResponse($arr);   
     }

     private function isRoomAvailable(Room $room, \DateTime $start, \DateTime $end, \Doctrine\ORM\PersistentCollection $reservations){

        foreach ($reservations as &$value){
            $isBefore = false;
            $isAfter = false;
            //jezeli pokoj nie jest ten sam to zacznij ponownie
            if($value->getRoom()->getRoomNumber() != $room->getRoomNumber()){
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
     * @Route("/api/room/{id}", name="show_room", methods={"GET"})
     * 
     * @SWG\Tag(name="room")
     * @SWG\Response(response=200, description="successful operation")
     * 
     *  @param int $id
     * 
     */
    public function showRoom(int $id, Request $request){
        $room = $this->getDoctrine()->getRepository(Room::class)->find($id);

        if (!$room) {
            throw $this->createNotFoundException('No room found for id '.$id);
        }
        
        $response = [
            "room_number" => $room->getRoomNumber(),
            "places_count" => $room->getPlacesCount(),
            "cost_per_day" => $room->getCostPerDay(),
            "type" => $room->getType(),
            "is_avaiable" => $room->getIsAvaiable(),
        ];

        return new JsonResponse(json_encode($response));     
     }

    /**
     * @Route("/api/room/{id}", name="update_room", methods={"PUT"})
     * 
     * @SWG\Tag(name="room")
     * @SWG\Response(response=200, description="successful operation")
     * 
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(ref=@Model(type=Room::class)),
     * )
     * 
     * @param int $id
     * @param Request $request
     * 
     */

    public function updateRoom(int $id, Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $entityManager = $this->getDoctrine()->getManager();
        $room = $entityManager->getRepository(Room::class)->find($id);
    
        if (!$room) {
            throw $this->createNotFoundException(
                'No room found for id '.$id
            );
        }
         
        $room->setRoomNumber($data['room_number']);
        $room->setPlacesCount($data['places_count']);
        $room->setCostPerDay($data['cost_per_day']);
        $room->setType($data['type']);
        $room->setIsAvaiable($data['is_avaiable']);
        $entityManager->flush();
    
        return new Response("room with id '.$id.' updated successfully!");
    }
     

}