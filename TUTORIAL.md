# Symfony2: Writing a contact bundle (part 1)
Writing simple bundles in Symfony2 is remarkably easy.  This mini series will look at writing a simple contact bundle (for contact us pages) and then how the bundle can be made more extensible.  This is a beginner level tutorial and uses Symfony2.1.
## Install Symfony2.1
First we need an install of Symfony to work with, if you dont already have one the Symfony website has some [simple instructions][1] on how to download and install vendors copied below:
``` shell
curl -s https://getcomposer.org/installer | php
```
After running this we got an error which required putting detect_unicode = Off into my php.ini file and running the command again.

Once composer is installed just run:
`php composer.phar create-project symfony/framework-standard-edition /sandbox`
We have composer installed in our localhost web root which means the above installs Symfony into our web root in a folder "sandbox". This takes a while but once it has run we need to update and install vendors:
``` shell
cd sandbox
php ../composer.phar update
php ../composer.phar install
```
Make sure app/cache and app/logs are writeable, if not follow the [Symfony tutorial][2] on setting them up.
## Create our bundle
Next we need to create a bundle skeleton for our contact us code to live in.  Again [Symfony2 documentation][3] exists for doing this and the relevant parts are below:

Open a terminal window and navigate to your new Symfony2 install:
``` shell
cd /path/to/sandbox
```

Run the Symfony2 bundle generator and answer the prompted questions:
``` shell
app/console generate:bundle
```
Below are some example answers that we generally follow, apart from the namespace we just hit return.  Make sure to say yes to updates of kernal and routing:
``` shell
Bundle namespace: Savvy/ContactBundle
Bundle name [SavvyContactBundle]: 
Target directory [/example/route/sandbox/src]:
Configuration format (yml, xml, php, or annotation) [annotation]:
Do you want to generate the whole directory structure [no]?
Do you confirm generation [yes]?
Confirm automatic update of your Kernel [yes]?
Confirm automatic update of the Routing [yes]?
```

Now we have a contact bundle made just check that app/AppKernel.php and app/config/routing.yml has your package configured:

``` php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
			//...
			new Savvy\ContactBundle\SavvyContactBundle(),
```
``` yaml
#app/config/routing.yml
#...
savvy_contact:
    resource: "@SavvyContactBundle/Controller/"
    type:     annotation
    prefix:   /
```

## Hello world
Now we have the bundle we want to rename the DefaultController.php file to ContactController.php.  Open up ContactController.php and rename the class to ContactController.  Update the annotated route to /contact, give it a name "savvy_contact" and remove all occurrences of $name (we always prepend "savvy_" to a route name so that it is unlikely to overwrite or be overwritten by another bundle):
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
        return array();
    }
}
```
Rename the Default views folder to Contact and then open Resources/views/Contact/index.html.twig.  Now just change the contents to "hello world".  If you have your site set up on a local server you can now go to "http://your.domain.dev/app_dev.php/contact" to see our hello world script run.

## Contact Entity and Form
You can create entities manually in Symfony2 but the fastest way is to use the command line entity generator.  The generator will not only create all annotations for the fields but will also create getters and setters.  These steps are from [http://symfony.com/doc/current/book/doctrine.html][4].  First we need to setup our app/config/parameters.yml. You will have to use your local database credentials:
[plain]
# app/config/parameters.yml
parameters:
    database_driver:    pdo_mysql
    database_host:      localhost
    database_name:      test_project
    database_user:      root
    database_password:  password
[/plain]
We can now create the database and an entity with the command line tool:
``` shell
php app/console doctrine:database:create
php app/console doctrine:generate:entity
```
When prompted answer the questions, an example set of answers including fields is below (remember blank answers means we just pressed return):
``` shell
The Entity shortcut name: SavvyContactBundle:Contact
Configuration format (yml, xml, php, or annotation) [annotation]: 

New field name (press <return> to stop adding fields): title
Field type [string]: 
Field length [255]: 

New field name (press <return> to stop adding fields): name
Field type [string]: 
Field length [255]: 

New field name (press <return> to stop adding fields): company
Field type [string]: 
Field length [255]: 

New field name (press <return> to stop adding fields): telephone
Field type [string]: 
Field length [255]: 

New field name (press <return> to stop adding fields): email
Field type [string]: 
Field length [255]: 

New field name (press <return> to stop adding fields): message
Field type [string]: text

New field name (press <return> to stop adding fields): created_at
Field type [datetime]: 

New field name (press <return> to stop adding fields): 

Do you want to generate an empty repository class [no]?    

Do you confirm generation [yes]? 
```
Open the generated entity to check out what has been generated and to set a table name.  We always specify a table name because mac's are not case sensitive but linux boxes are!
``` php
//Entity/Contact.php
//..
/**
 * Savvy\ContactBundle\Entity\Contact
 *
 * @ORM\Table(name="contact")
 * @ORM\Entity
 */
class Contact
```

The final bit of generating for now is the Form.  This is very simple, jump on the command line again and generate the form using your entity shortname (ours is SavvyContactBundle:Contact):
``` shell
php app/console doctrine:generate:form SavvyContactBundle:Contact
```

Thats it, we have now installed Symfony2, created a contact bundle skeleton, created a contact entity and a contact form with a few commands and a couple of lines of PHP.  In the next tutorial we will start to customise the form and manage posting data with it.

Thanks for reading, peace out

Luke

[1]:   http://symfony.com/doc/current/book/installation.html
[2]:   http://symfony.com/doc/current/book/installation.html#configuration-and-setup
[3]:   http://symfony.com/doc/current/bundles/SensioGeneratorBundle/commands/generate_bundle.html
[4]:   http://symfony.com/doc/current/book/doctrine.html