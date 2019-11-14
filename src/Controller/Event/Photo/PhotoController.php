<?php

namespace App\Controller\Event\Photo;

use App\Service\Api;
use App\Entity\Photo;
use App\Entity\Comment;
use App\Form\CommentType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PhotoController extends AbstractController
{
    /**
    * @Route("/photo/{id}", name="photo_show")
    */
    public function photo_show(Photo $photo, Request $request, $id ){
        $liked = false;
        $comment = new Comment();
        $user = $this->getUser();
        $formComment = $this->createForm(CommentType::class, $comment);

        $formComment->handleRequest($request);

        if ($formComment->isSubmitted() && $formComment->isValid()) {
            
            $comment->setauthor( $user = $this->getUser() );
            $comment->setPhoto( $photo );

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();
            return $this->redirectToRoute('photo_show', [
                'id' => $id,
            ]);


        }

        return $this->render('event/photo/photo_show.html.twig', [
            'photo' => $photo,
            'formComment' => $formComment->createView(),
        ]);
    }
    
    /**
    * @Route("/event/photo/{id}/like", name="like_photo")
    */
    public function like_photo(Photo $photo, $id){
        //gets the actual user
        $user = $this->getUser();
        //likes or unlike it
        if($photo->getUsers()){
            if (!in_array($user->getUsername(), $photo->getUsers())) 
            { 
                $photo->addUser( $user );
            }else{
                $photo->removeUser($user);
            }
        }else{
            $photo->addUser( $user );
        }
        //saves the photo's state in database
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($photo);
        $entityManager->flush();
        
        //redirect to photo show route
        return $this->redirectToRoute('photo_show', [
            'photo' => $photo,
            'id' => $id
        ]);
    }
    /**
    * @Route("/comment/delete/{id}", name="delete_comment")
    */
    public function delete_comment(Comment $comment, Request $request){

        
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $id = $comment->getPhoto()->getId();
            $photo = $comment->getPhoto();
            $photo->removeComment($comment);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($photo);
            $entityManager->remove($comment);
            $entityManager->flush();

        }
        return $this->redirectToRoute('photo_show', [
        'photo' => $photo,
        'id' => $id
        ]);
        

    }

    /**
    * @Route("/comment/{id}/report", name="report_comment")
    */
    public function report_comment(Comment $comment, \Swift_Mailer $mailer, Api $api){
        $user = $this->getUser();
        
        $author = $comment->getAuthor();

        $message = (new \Swift_Message('Report'))
            ->setFrom($user->getUsername())
            ->setTo('montemonttheophile@gmail.com')//the bde's mail
            ->setBody(
                $this->renderView(
                    // templates/emails/order.html.twig
                    'emails/report.html.twig',
                    [
                    'author' => $author,
                    'item' => $comment,
                    'user' => $user
                    ]
                ),'text/html');
    
            $mailer->send($message);
        
        return $this->redirectToRoute('event_before');
    }

    /**
    * @Route("/photo/delete/{id}", name="delete_photo")
    */
    public function delete_photo(Photo $photo,$id, Request $request){

        
        if ($this->isCsrfTokenValid('delete'.$photo->getId(), $request->request->get('_token'))) {
            $id = $photo->getEvenement()->getId();
            $photo->clearComments();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($photo);
            $entityManager->flush();
    
        }

        return $this->redirectToRoute('show_event', [
        'photo' => $photo,
        'id' => $id
        ]);
    }

    /**
    * @Route("/photo/{id}/report", name="report_photo")
    */
    public function report_photo(Photo $photo, \Swift_Mailer $mailer, Api $api){

        $id = $photo->getEvenement()->getId();

        $user = $this->getUser();
        $author = $photo->getAuthor();

        $message = (new \Swift_Message('Report'))
            ->setFrom($user->getUsername())
            ->setTo('montemonttheophile@gmail.com')//the bde's mail
            ->setBody(
                $this->renderView(
                    // templates/emails/report.html.twig
                    'emails/report.html.twig',
                    [
                        'author' => $author,
                        'item' => $photo,
                        'user' => $user
                    ]
                ),'text/html');
    
            $mailer->send($message);
        
            return $this->redirectToRoute('show_event', [
                'id' => $id
                ]);
    }

}
