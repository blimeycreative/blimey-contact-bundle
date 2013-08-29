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
        $formname = $this->getParameter('savvy_contact.formtype.namespace');
        $form = $this->createForm(new $formname);

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
        $formname = $this->getParameter('savvy_contact.formtype.namespace');
        $form = $this->createForm(new $formname, $contact);
        //Bind the posted data to the form
        $form->bind($this->getRequest());
        //Make sure the form is valid before we persist the contact
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($contact);
            $em->flush();

            $parameters = $this->container->hasParameter("savvy_contact.contact_thanks_message") ? $this->getParameter(
                "savvy_contact.contact_thanks_message"
            ) : array();
            $message = $this->get('translator')->trans('ContactThanksMessage', $parameters);
            $this->get("session")->setFlash('contact_thanks', $message);

            $this->sendNotifiactionEmail($contact);

            $this->sendConfirmationEmail($contact);

            return $this->redirect($this->generateUrl("savvy_contact"));
        }

        return array(
            'form' => $form->createView()
        );
    }

    protected function getParameter($parameter)
    {
        if ($this->get('session')->has($parameter)) {
            return $this->get('session')->get($parameter);
        }

        return $this->container->getParameter($parameter);
    }

    protected function sendNotifiactionEmail(Contact $contact)
    {
        $response = $this->render(
            $this->getParameter('savvy_contact.notification_template'),
            array('contact' => $contact)
        );
        $subject_params = $this->container->hasParameter("savvy_contact.notification_subject") ? $this->getParameter(
            "savvy_contact.notification_subject"
        ) : array();

        $subject = $this->get('translator')->trans('ContactNotificationSubject', $subject_params);

        $this->sendMail(
            $this->getParameter('savvy_contact.notification_addresses'),
            $contact->getEmail(),
            $subject,
            $response->getContent()
        );
    }

    protected function sendConfirmationEmail(Contact $contact)
    {
        $subject_params = $this->container->hasParameter("savvy_contact.confirmation_subject") ? $this->getParameter(
            "savvy_contact.confirmation_subject"
        ) : array();
        $subject = $this->get('translator')->trans('ContactConfirmationSubject', $subject_params);
        $response = $this->render(
            $this->getParameter('savvy_contact.confirmation_template'),
            array('contact' => $contact)
        );

        $this->sendMail(
            $contact->getEmail(),
            $this->getParameter('savvy_contact.confirmation_from_address'),
            $subject,
            $response->getContent()
        );
    }

    protected function sendMail($to, $from, $subject, $message)
    {
        $email = \Swift_Message::newInstance($subject, $message, 'text/html')
            ->setFrom($from)
            ->setTo($to);
        $this->get('mailer')->send($email);
    }
}