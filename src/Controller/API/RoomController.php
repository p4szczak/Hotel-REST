<?php

namespace App\Controller\API;


use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Room;

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
     * @param Request $request
     */
    public function listAvaiableRooms(Request $request){


        $rooms = $this->getDoctrine()->getRepository(Room::class)->findAll();
        $arr = array();
        foreach ($rooms as &$value) {
            if(!$value->getIsAvaiable()) continue;
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