<?php


namespace App\Notification ;

use Swift_Message ;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Twig\Error\RuntimeError;
use App\Entity\User;

class activation  
{
    /**
     * Propriété contenant le module d'envoi de mails 
     * 
     * @var \Swift_Mailer
     */
    private $mailer;
    /**
     * Propriété contenant l'environement Twig 
     * 
     * @var Environment 
     */
    private $renderer ;

    public function __construct (\Swift_Mailer $mailer , Environment $renderer)
    {
        $this->mailer = $mailer;
        $this->renderer = $renderer ;
    }
    /**
     * Méthode de notification (envoi d'un mail)
     * 
     * @return void 
     */
    public function notifyActiver(User $user)
        { 
            $message = (new \Swift_Message('Activation de votre compte'))
                ->setFrom('admin@gmail.com')
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderer->render(
                        'Emails/activationCmpt.html.twig', ['token' => $user->getActivated()]
                    ),
                    'text/html'
                );

            return $this->mailer->send($message);
            
    }
      


   

}
