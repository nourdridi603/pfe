<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Notification\CreationFournisseurNotif;
use App\Notification\activation;

class UserController extends AbstractController
{
    /**
     * @var activation
     */
    private $notify_activer; 
    public function __construct(activation $notify_activer)
    {
      $this->notify_activer=$notify_activer;
    }

    /**
     * @Route("/inscriptionClient",name="addClient")
     */
    public function addClient(UserPasswordEncoderInterface $encoder,Request $req, \Swift_Mailer $mailer){

        $client=new User();
        $form = $this->createForm(UserType::class, $client);
        $form->handleRequest($req);
        if ($form->isSubmitted() && $form->isValid()) {
            $encoded = $encoder->encodePassword($client, $client->getPassword());
            $manager = $this->getDoctrine()->getManager();
            $client->setRoles(["Client"]);
            $client->setActivated(md5(uniqid()));
            $client->setPassword($encoded);
            $manager->persist($client);
            // mail
            $this->notify_activer->notifyActiver($client);
            $manager->flush();

            return $this->redirectToRoute("accueil");  
        }
            return $this->render('Client/inscription.html.twig', [
                'form' => $form->createView()
                
            ]);
    }


    /**
     * @Route("/activation/{token}", name="activation" )
     */
    public function activation($token, UserRepository $userRepo)
    {
        $user = $userRepo->findOneBy(['activated' => $token]);
        if(!$user)
        {
            throw $this->createNotFoundException('Cet utilisateur n\'existe pas');
        }
        $manager = $this->getDoctrine()->getManager();
        $user->setActivated("active");
        $manager->persist($user);
        $manager->flush();

        $this->addFlash('success', 'Vous avez bien activé votre compte' );

        return $this->redirectToRoute('accueilClient');
    }
}