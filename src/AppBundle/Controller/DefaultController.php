<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Message;
use Mailgun\Mailgun;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('index.html.twig');
    }

    /**
     * @Route("/post", name="post_message")
     * @Method ({"POST"})
     * @param Request $request
     * @return RedirectResponse
     */
    public function postAction(Request $request)
    {
        $hash = md5(uniqid(true));
        $message = new Message();
        $message->setHash($hash);
        $message->setMessage($request->get('message'));

        $em = $this->getDoctrine()->getManager();
        $em->persist($message);
        $em->flush();

        $mailgun = $this->get('mailgun');
        $mailgun->sendMessage(
            $this->getParameter('mailgun_domain'),
            [
                'from' => 'noreply@destructy.com',
                'to' => $request->get('email'),
                'subject' => 'You have received new message on Destructy!',
                'html' => $this->render(
                    ':emails:message.html.twig',
                    [
                        'hash' => $hash,
                        'host' => $this->getParameter('host'),
                    ]
                )->getContent()
            ]
        );

        $this->addFlash('notice', 'Message has been sent');

        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/message/{hash}", name="show_message")
     * @param $hash
     * @return Response
     */
    public function messageAction($hash)
    {
        $message = $this->getDoctrine()->getRepository(Message::class)->findOneBy(['hash' => $hash]);
        $messageContent = $message->getMessage();
        $em = $this->getDoctrine()->getManager();
        $em->remove($message);
        $em->flush();
        return $this->render(
            'message.html.twig',
            [
                'message_content' => $messageContent,
            ]
        );
    }
}
