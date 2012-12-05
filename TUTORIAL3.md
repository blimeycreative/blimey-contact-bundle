# Symfony2: Writing a contact bundle (Part 3)
This part of the tutorial will cover sending confirmation and notification emails with [swiftmailer][3] and creating a simple [CRUD][4] for our contact entity.    If you have missed out on part 1 or part 2 of this tutorial, you can find them here:
Part 1: [http://blog.savvycreativeuk.com/2012/12/symfony2-writing-a-contact-bundle-part-1/][1]
Part 2: [http://blog.savvycreativeuk.com/2012/11/symfony2-contact-bundle-part-2/][2]

## Sending confirmation emails with swiftmailer
Symfony2 has a [swiftmailer][3] bundle as a default dependency for the framework.  This is good news for us as working with swiftmailer is a lot easier than messing around with the [php mail][5] function because it manages email headers and the like for us.  Swiftmailer is also extremely helpful when we want to [add an attachment][6] to our email. Adding an attachment with the mail function is not such a simple process.

If you have an SMTP server you can use SMTP as your transport method but for this tutorial we will simply use the standard mail method.  This is configured in the app/config/parameters.yml:

```
#app/config/parameters.yml
#...
    mailer_transport:  mail
    mailer_host:       localhost
    mailer_user:       ~
    mailer_password:   ~
```

The full configuration is documented here: [http://symfony.com/doc/current/reference/configuration/swiftmailer.html#full-default-configuration][7]
	
Now that the mail transport is configured we can send a couple of emails from our ContactController, one to the "contacter" and one to us to notify us of the contact.  We have already looked at translations so lets also use them to make the email subjects configureable.  We wont make the body copy a translation because we have another plan for that later on in this tutorial:
``` php
	//Controller/ContactController.php
	//...
	public function submitAction()
    {
        //Create a new contact entity instance
        $contact = new Contact();
        $form = $this->createForm(new ContactType(), $contact);
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

            //Send us a notification email with translation subject
            $subject = $this->get('translator')->trans('ContactNotificationSubject');
            $notification = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom($contact->getEmail())
                ->setTo('luke@savvycreativeuk.com') //substitute with your email
                ->setBody("New contact submitted dude, check it out\n\n {$contact->getMessage()}", 'text/html')
            ;
            //Send the contacter a message with translation subject
            $subject = $this->get('translator')->trans('ContactConfirmationSubject');
            $confirmation = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom('hello@savvycreativeuk.com') //substitute with your email
                ->setTo($message->getEmail()) 
                ->setBody('Thanks for getting in touch', 'text/html')
            ;
            //Actually send the messages
            $this->get('mailer')->send($notification);
            $this->get('mailer')->send($confirmation);

            return $this->redirect($this->generateUrl("savvy_contact"));
        }
        //...
```
Update the Resources/translations/messages.en.yml with some subjects:
```
#messages.en.yml
#...
'ContactNotificationSubject': New contact submitted dude!
'ContactConfirmationSubject': Thanks for contacting Savvy
```

## Parameters and email templates
To continue along the lines of being configurable, we want our contact bundle email addresses and templates to be overwritten if needed in app/config/config.yml.  We will create default parameters in our bundle's Resources/config/services.yml that can then be overwritten in the global config file:
```
#Resources/config/services.yml
parameters:
    #Set up the email address defaults
    contact.notification_addresses:     luke@savvycreativeuk.com, luke2@savvycreativeuk.com
    contact.confirmation_from_address:  hello@savvycreativeuk.com
    #Set up the email template defaults
    contact.notification_template:      SavvyContactBundle:Email:notification.html.twig
    contact.confirmation_template:      SavvyContactBundle:Email:confirmation.html.twig
```

It is likely that your bundle is set up to read Resources/config/services.xml rather than .yml so we need to update DependencyInjection/SavvyContactExtension.php to read .yml instead:
``` php
//DependencyInjection/SavvyContactExtension.php
//...
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        //Yaml loader not Xml loader
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        //Load Resources/config/services.yml
        $loader->load('services.yml');
    }
```

Now that we have defined the email templates, lets create the actual files in Resources/views/Email:
``` html
<!-- Resources/views/Email/confirmation.html.twig -->
<!DOCTYPE HTML>
<html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <title>New contact</title>
    </head>
    <body>
        <table>
            <tbody>
            <tr>
                <td>
                    <p>New contact received, details below dude:</p>
                </td>
            </tr>
            <tr>
                <td>
                    <ul>
                        <li><strong>Name</strong>:{{ contact.title | title }} {{ contact.name }}</li>
                        <li><strong>Company</strong>:{{ contact.company }}</li>
                        <li><strong>Telephone</strong>:{{ contact.telephone }}</li>
                        <li><strong>Email</strong>: {{ contact.email }}</li>
                        <li><strong>Message</strong>: {{ contact.message }}</li>
                    </ul>
                </td>
            </tr>
            </tbody>
        </table>
    </body>
</html>
```
``` html
<!-- Resources/views/Email/confirmation.html.twig -->
<!DOCTYPE HTML>
<html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <title>Thanks for getting in touch</title>
    </head>
    <body>
        <table>
            <tbody>
            <tr>
                <td>
                    <p>Hi {{ contact.title | title }} {{ contact.name | title }}</p>
                </td>
            </tr>
            <tr>
                <td>
                    <p>Thanks for getting in touch dude, we will respond to your message shortly :)</p>
                </td>
            </tr>
            <tr>
                <td>
                    <p>Kind Regards</p>
                </td>
            </tr>
            <tr>
                <td>
                    <p>Awesome contact bundle team</p>
                </td>
            </tr>
            </tbody>
        </table>
    </body>
</html>
```
And finally we can set the swiftmailer calls in Controller/ContactController.php to use our new templates and addresses:
``` php
	//Controller/ContactController.php
	//...
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
```

Now just clear the dev cache and submit a contact to see the email templates and addresses get used. Awesome!
## Contact CRUD
Finally we want to create a system to manage the contact submissions.  We call this a CRUD which stands for Create, Read, Update and Delete. Symfony2 command line tool has helped us a lot already, however its not done yet.  The tool has a CRUD generator that can create a full CRUD from an entity.  For the contact system we don't actually need a full CRUD, more just an "R" but we will create it anyway just for the added functionality in case its desired. If you just want an "R" the CRUD generation tool helpfully gives us an option to leave out the write actions "CUD".

Before we can generate the CRUD we need to make a few tweaks to our files as the CRUD generator is slightly limited in its file creation abilities:

* We need to temporarily rename our ContactController.php file to _ContactController.php as the CRUD generator is going to create a ContactController.php file.
* For the same reason we need to rename our Resources/views/Contact/index.html.twig to Resources/views/Contact/form.html.twig.
* The final tweak is to change both occurrences of our @Template annotation in ContactController.php to: `@Template("SavvyContactBundle:Contact:form.html.twig")`

Now that we have made these small tweaks we can use our Contact entity short name to generate a CRUD:
``` shell
php app/console doctrine:generate:crud
The Entity shortcut name: SavvyContactBundle:Contact
Do you want to generate the "write" actions [no]? yes
Configuration format (yml, xml, php, or annotation) [annotation]:
Routes prefix [/contact]: /admin/contact
Do you confirm generation [yes]?
```
Now we can just rename the newly generated ContactController.php to AdminContactController.php and the class name to:
``` php
//Controller/AdminContactController.php
//...
class AdminContactController extends Controller
```
Create a new folder "Resources/views/AdminContact" and drag the index.html.twig , new.html.twig, edit.html.twig and show.html.twig files from the Contact folder into the new AdminContact folder.

We are now ready to test our admin system by visiting your.domain.com/app_dev.php/admin/contact. You should see a table of all the test contacts we have made.  Fom this table you can add, edit, view and delete contact submissions, how easy was that!!

In the next tutorial we will look at setting our bundle up so it can be installed by composer and extending the bundle in a separate Symfony2 project.

Thanks for reading, peace out

Luke

[1]: http://blog.savvycreativeuk.com/2012/12/symfony2-writing-a-contact-bundle-part-1/
[2]: http://blog.savvycreativeuk.com/2012/12/symfony2-contact-bundle-part-2/
[3]: http://swiftmailer.org/
[4]: http://en.wikipedia.org/wiki/Create,_read,_update_and_delete
[5]: http://php.net/manual/en/function.mail.php
[6]: http://swiftmailer.org/docs/messages.html#attaching-files
[7]: http://symfony.com/doc/current/reference/configuration/swiftmailer.html#full-default-configuration