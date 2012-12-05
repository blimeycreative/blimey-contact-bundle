# Symfony2: Writing a contact bundle (part 2)
This part of the tutorial will cover configuring forms and entities and, submitting and saving data.  If you have missed out on part one of this tutorial you can find it here: [http://blog.savvycreativeuk.com/2012/12/symfony2-writing-a-contact-bundle-part-1/][1]

## Show the form
Full documentation on Symfony forms is available here: [http://symfony.com/doc/current/book/forms.html][2]
	
In part 1 we created a form class with the command line generator.  To display the form in our browser we need to do a couple of things.  First we need to import our form's namespace into our ContactController (remember to substitute "Savvy" for your company/namespace name):
``` php
	//Controllers/ContactController.php
	//..
	use Savvy\ContactBundle\Form\ContactType;
```
Now that we have imported that namespace we can call our form with "new ContactType()" anywhere in ContactController.php.  With this in mind, lets pass an instance of our form through to the view.
``` php
//ContactController.php
//...
class ContactController extends Controller
{
    /**
     * @Route("/contact", name="savvy_contact")
     * @Template()
     */
    public function indexAction()
    {
    	//use the createForm method to get a symfony form instance of our form
    	$form = $this->createForm(new ContactType());

        return array(
        	//pass the form to our template, must be a form view using ->createView()
        	'form' => $form->createView()
        	);
    }
}
```
This passes through the form to our view so in Resources/views/Contact/index.html.twig we just need to render it.  Symfony has a few form extensions for twig, the simplest (but least customisable in terms of HTML) is "form_widget" which renders the whole form except the submit button and actual &lt;form&gt; elements.  We need to write the extra bits of HTML and include an action url using symfony's [path twig extension][4]:
``` js
//Resources/views/Contact/index.html.twig
//Note that we are passing a route name to "path" that we have created "savvy_contact" 
//so the form will post to your.domain.com/contact
<form action="{{path("savvy_contact")}}" method="post">
	{{ form_widget(form) }}
	<p>
		<button type="submit">Send Message</button>
	</p>
</form>
```
Submitting this form empty will flag browser errors in Chrome and Firefox as Symfony2 forms come with HTML5 attributes including validation.  This can be turned off if you want by adding the novalidate attribute to the form tag or formnovalidate to the submit tag.  We usually leave it on but it is hard to style so it may not be for you: [http://stackoverflow.com/questions/5713405/how-do-you-style-the-html5-form-validation-messages/5965505#5965505][3]

## Sticky form
If we submit the form it will post to the same URL and come back empty.  This is because, although we have submitted data, we have not bound the posted data to the form.  We will make a submit handling function to take the posted data and bind it to the form:
``` php
class ContactController extends Controller
{
    //...

    /**
     * @Route("/contact/submit", name="savvy_submit_contact")
     * @Method("POST")
     * @Template("SavvyContactBundle:Contact:index.html.twig")
     */
    public function submitAction()
    {
    	$form = $this->createForm(new ContactType());
    	//Bind the posted data to the form
    	$form->bind($this->getRequest());

    	return array(
        	'form' => $form->createView()
        	);
    }
}
```
The @Method annotation below allows us to specify the type of request, setting @Method("POST") means only POST requests can access this function.  You will notice if you try and visit your.domain.com/app_dev.php/contact/submit without submitting a form you will get an error explaining that the GET method is not allowed. We also want to use the index template to render the form so we have specified the template using the @Template annotation.

We need to change the action url of our form to the new route name "savvy_submit_contact" before we can actually submit it and see the change:
``` js
//Resources/views/Contact/index.html.twig
<form action="{{path("savvy_submit_contact")}}" method="post">
```
Now when we submit the form, the url we post to is your.domain.com/contact/submit and the returned form has the submitted data in it. Sweet!
## Tweaking the form
Currently all of our form fields are required and are all default types (mostly text) depending on the field type guessing [http://symfony.com/doc/current/book/forms.html#field-type-guessing][5]. Each field in Form/ContactType.php can take an array of settings as its third argument. We will tweak a few fields to make them more user friendly and more contact formish:
``` php
//Form/ContactType.php
//...
public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'choice', array(
                //An array of choices the user can pick
                'choices' => array(
                    'mr' => 'Mr',
                    'mrs' => 'Mrs',
                    'ms' => 'Ms',
                    'miss' => 'Miss',
                    'dr' => 'DR'
                    ),
                //Set an empty value so the user doesnt accidently submit "mr" without checking
                'empty_value' => 'Please select'
                ))
            ->add('name')
            ->add('company','text', array(
                //Stop HTML5 required validation
                'required'=>false
                ))
            ->add('telephone','text', array(
                'required'=>false
                ))
            ->add('email')
            ->add('message', 'textarea', array(
                //attr lets us set any key => value pair as a html attribute
                "attr" => array("cols" => "60", "rows" => 4) 
                ))
            //Dont let the user set created_at, you can remove this line rather than comment it out
            //->add('created_at')
        ;
    }
```
Refeshing the form in your browser should show the updated fields.
## Tweaking the entity
Because we have changed the form to have fields that are not required, we need to update the entity to match.  On regular column annotations this is as simple as adding nullable=true:
``` php
//Entity/Contact.php
//...
/**
* @var string $company
*
* @ORM\Column(name="company", type="string", length=255, nullable=true)
*/
private $company;

/**
* @var string $telephone
*
* @ORM\Column(name="telephone", type="string", length=255, nullable=true)
*/
private $telephone;
```
The other tweak we want to make is to have the created_at date set automatically when the entity is persisted.  We can do this using Doctrine's [@HasLifecycleCallbacks][6] annotation:
``` php
/**
* Savvy\ContactBundle\Entity\Contact
*
* @ORM\Table(name="contact")
* @ORM\Entity
* @ORM\HasLifecycleCallbacks
*/
class Contact
{

//...

   /**
    * Make sure PrePersist is camel cased like below, "prePersist" will fail
    * @ORM\PrePersist()
    */
    public function setTimeStamp()
    {
        $this->created_at = new DateTime();
    }
```
Now we just need doctrine to update our database for us.  On the command line force an update:
[shell]
php app/console doctrine:schema:update --force
[/shell]
## Saving a contact
To test the entity tweaks and progress our contact bundle we want to save a submitted contact form in our database.  Also to be user friendly we should return a thanks for contacting us response!
Saving an entity is reasonably simple.  We can initalise the ContactType with an instance of our Contact entity.  When we bind the request to the form, the entity will be populated for us to persist. Simples:
``` php
//Controller/ContactController.php
//...
   /**
    * @Route("/contact/submit", name="savvy_submit_contact")
    * @Method("POST")
    * @Template("SavvyContactBundle:Contact:index.html.twig")
    */
    public function submitAction()
    {
        //Create a new contact entity instance
        $contact = new Contact();
        $form = $this->createForm(new ContactType(), $contact);
        //Bind the posted data to the form
        $form->bind($this->getRequest());
        //Make sure the form is valid before we persist the contact
        if($form->isValid()){
            //Get the entity manager and persist the contact
            $em = $this->getDoctrine()->getManager();
            $em->persist($contact);
            $em->flush();
            //Redirect the user and add a thank you flash message
            $this->get("session")->setFlash('contact_thanks', 'thanks');

            return $this->redirect($this->generateUrl("savvy_contact"));
        }

        return array(
            'form' => $form->createView()
        );
    }

```
Remember we will need to include a use statment for our Contact entity in our ContactController.php:
``` php
//Controller/ContactController.php
//..
use Savvy\ContactBundle\Entity\Contact;
```
## Custom thank you message
Finally we are going to show the user our flash message if they submit the form correctly.  To make our bundle more flexible we will add the thank you message as a translatable string using Symfony's translations: [http://symfony.com/doc/master/book/translation.html][7].
First we need to request a translation if it exists in our ContactController:
``` php
//Controller/ContactController.php
//...
    public function submitAction()
    {
        //...

        //Redirect the user and add a thank you flash message
        //The string 'ContactThanksMessage' can now be overwritten by a translation
        $message = $this->get('translator')->trans('ContactThanksMessage');
        $this->get("session")->setFlash('contact_thanks', $message);

        return $this->redirect($this->generateUrl("savvy_contact"));
    }

    return array(
        'form' => $form->createView()
    );
}

```
Next we need to create a translation folder in our bundles Resource folder: Resources/translations

Inside the translations folder we will make a messages.en.yml where en is our language code (english).  Translations can then be added into our messages.en.yml file:
```
#Resources/translations/messages.en.yml
'ContactThanksMessage':  Thanks for getting in touch dude!
```
Update your app/config/config.yml and app/config/parameters.yml to set the fallback translation
```
#app/config/config.yml
framework:
    #...
    translator:      { fallback: %locale% }
```
```
#app/config/parameters.yml
parameters:
#...
locale:            en
```
Clear your dev cache on the command line:
```
php app/console cache:clear
```
And last but not least, let's show the flash message in our index.html.twig template:
``` html
{% if app.session.hasFlash('contact_thanks')  %}
    <p>{{ app.session.getFlash('contact_thanks') }}</p>
{% else %}
    <form action="{{path("savvy_submit_contact")}}" method="post">
        {{ form_widget(form) }}
        <p>
            <button type="submit">Send Message</button>
        </p>
    </form>
{% endif %}
```
Thats it!  Submit the form and see your translated thank you message displayed.  Check your database for the submitted contact data.

In the next tutorial we will look at using the swiftmailer bundle to send email confirmation and notification messages and creating a simple CRUD.

Thanks for reading, peace out

Luke

[1]:   http://blog.savvycreativeuk.com/2012/12/symfony2-writing-a-contact-bundle-part-1/
[2]:   http://symfony.com/doc/current/book/forms.html
[3]:   http://stackoverflow.com/questions/5713405/how-do-you-style-the-html5-form-validation-messages/5965505#5965505
[4]:   http://symfony.com/doc/2.0/book/templating.html#linking-to-pages
[5]:   http://symfony.com/doc/current/book/forms.html#field-type-guessing
[6]:   http://docs.doctrine-project.org/en/2.0.x/reference/annotations-reference.html#annref-haslifecyclecallbacks
[7]:   http://symfony.com/doc/master/book/translation.html