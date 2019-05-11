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
     * @Route("/api/room", name="add_room", methods={"POST"})
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
     * @param Request $request
     * 
     */
    public function addRoom(Request $request) {
        $data = json_decode($request->getContent(), true);
        if (!$request) {
            return $this->respondValidationError('Please provide a valid request!');
        }

        $room = new Room();
        $room->setRoomNumber($data['room_number']);
        $room->setPlacesCount($data['places_count']);
        $room->setCostPerDay($data['cost_per_day']);
        $room->setType($data['type']);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($room);
        $entityManager->flush();
        return new Response('Saved new room with id '.$room->getId());
     }
}