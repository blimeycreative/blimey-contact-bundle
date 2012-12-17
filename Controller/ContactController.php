<?php

namespace Savvy\ContactBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Savvy\ContactBundle\Entity\Contact;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends Controller
{
    /**
     * @Route("/contact", name="savvy_contact")
     * @Template("SavvyContactBundle:Contact:form.html.twig")
     */
    public function indexAction()
    {
        //use the createForm method to get a symfony form instance of our form
        $form = $this->createForm(new $this->get('contact.formtype.namespace'));

        return array(
            //pass the form to our template, must be a form view using ->createView()
            'form' => $form->createView()
        );
    }


    /**
     * @Route("/contact/submit", name="savvy_submit_contact")
     * @Method("POST")
     * @Template("SavvyContactBundle:Contact:form.html.twig")
     */
    public function submitAction()
    {
        //Create a new contact entity instance
        $contact = new Contact();
        $form = $this->createForm(new $this->get('contact.formtype.namespace'), $contact);
        //Bind the posted data to the form
        $form->bind($this->getRequest());
        //Make sure the form is valid before we persist the contact
        if ($form->isValid()) {
            //Get the entity manager and persist the contact
            $em = $this->getDoctrine()->getManager();
            $em->persist($contact);
            $em->flush();
            //Redirect the user and add a thank you message
            $message = $this->get('translator')->trans('ContactThanksMessage');
            $this->get("session")->setFlash('contact_thanks', $message);

            //Send us a notification email
            //Get the to addresses and make an array for swiftmailer
            $addresses = explode(',', $this->container->getParameter('contact.notification_addresses'));
            //Render the correct template passing through the contact entity
            $response = $this->render(
                $this->container->getParameter('contact.notification_template'),
                array('contact' => $contact)
            );
            $subject = $this->get('translator')->trans('ContactNotificationSubject');
            $notification = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom($contact->getEmail())
                ->setTo($addresses)
            //Set the content to the template string
                ->setBody($response->getContent(), 'text/html');
            //Send the "contacter" a message
            $subject = $this->get('translator')->trans('ContactConfirmationSubject');
            //Render the correct template passing through the contact entity
            $response = $this->render(
                $this->container->getParameter('contact.confirmation_template'),
                array('contact' => $contact)
            );
            $confirmation = \Swift_Message::newInstance()
                ->setSubject($subject)
            //Set the from address to the correct parameter
                ->setFrom($this->container->getParameter('contact.confirmation_from_address'))
                ->setTo($contact->getEmail())
            //Set the content to the template string
                ->setBody($response->getContent(), 'text/html');
            //Actually send the messages
            $this->get('mailer')->send($notification);
            $this->get('mailer')->send($confirmation);

            return $this->redirect($this->generateUrl("savvy_contact"));
        }

        return array(
            'form' => $form->createView()
        );
    }

}