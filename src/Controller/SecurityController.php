<?php

namespace App\Controller;

use App\Form\ResetPassType;
use App\Repository\UserRepository;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response{
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(){
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

      /**
     * @Route("/accueilClient",name="accueilClient")
     */
    public function homeClient(){
        return $this->render("Client/home.html.twig");
    }
     /**
     * @Route("/accueilAdmin",name="accueilAdmin")
     */
    public function homeAdmin(){
        return $this->render("Admin/home.html.twig");
    }
     /**
     * @Route("/accueil",name="accueil")
     */
    public function home(){
        return $this->render("accueil.html.twig");
    }



    /**
     * @Route("/oubli-pass", name="app_forgetten_password")
     */
    public function forgettenPass(Request $request, UserRepository $userRepo, \Swift_Mailer $mailer, TokenGeneratorInterface $tokenGenerator )
    {
        //On cr??e le formulaire 
        $form =$this->createForm(ResetPassType::class);
        //On traite le formulaire 
        $form->handleRequest($request);
        //Si le formulaire est valide 
        if($form->isSubmitted() && $form->isValid())
        {   $donnees = $form->getData();
            // On cherche si un utilisateur a cet email
            $user = $userRepo->findOneByEmail($donnees['email']);
            //Si l'utilisateur n'existe pas 
            if(!$user)
            {    //On envoie un message flash
                $this->addFlash('danger', 'Cet email n\'existe pas ');
                return $this->redirectToRoute('app_login');
            }
            //On g??n??re un token 
            $token = $tokenGenerator->generateToken();
            try{$user->setResetToken($token);
                $manager = $this->getDoctrine()->getManager();
                $manager->persist($user);
                $manager->flush();
            }catch(\Exception $e){
                $this->addFlash('warning', 'Une erreur est survenue : '.$e->getMessage());
                return $this->redirectToRoute('app_login');}
            //On g??n??re l'URL de r??initialisation de mot de passe 
            $url = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
            // On envoie le message 
            $message = (new \Swift_Message('Mot de passe oubli??'))
            ->setFrom('no-reply@gmail.com')
            ->setTo($user->getEmail())
            ->setBody(
                $this->render(
                    'Emails/MdpOubli.html.twig', ['url' => $url]
                ),
                "text/html");
            //On envoie l'e-mail
            $mailer->send($message);
            //On cr??e le message flash
            $this->addFlash('message', 'Un email de r??initialisation de mot de passe vous a ??t?? envoy?? ');
            return $this->redirectToRoute('app_login');
        }
        //On envoie vers la page de demande de l'email 
        return $this->render('security/forgetten_password.html.twig', ['emailForm' => $form->createView()]);}








    /**
     * @Route("/reset-pass/{token}", name="app_reset_password")
     */
    public function resetPassword($token, Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        // On cherche l'utilisateur avec le token fourni 
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['reset_token' => $token]);
        if(!$user)
        {
            $this->addFlash('danger', 'Token inconnu');
            return $this->redirectToRoute('app_login');
        }
        //Si le formulaire est envoy?? en m??thode  POST
        if($request->isMethod('POST')){
            //On supprime le token 
            $user->setResetToken(null);
            //On chiffre le mot de passe 
            $user->setPassword($passwordEncoder->encodePassword($user, $request->request->get('password')));
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($user);
            $manager->flush();
            $this->addFlash('message', 'Mot de passe modifi?? avec succ??s');
            return $this->redirectToRoute('app_login');
        }
        else {
            return$this->render('security/reset_password.html.twig', ['token'=>$token]);}
    }
}
