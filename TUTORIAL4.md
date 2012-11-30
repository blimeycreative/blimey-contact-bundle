# Symfony2: Writing a contact bundle (Part 4)
This part of the tutorial will cover publishing our contact bundle with composer and packagist.  We will also cover installing our bundle via composer in a different Symfony2 project.  If you have missed out on any of the previous tutorials, you can find them here:

Part 1: [http://blog.savvycreativeuk.com/2012/11/symfony2-contact-bundle-part-1/][1]

Part 2: [http://blog.savvycreativeuk.com/2012/11/symfony2-contact-bundle-part-2/][2]

Part 3: [http://blog.savvycreativeuk.com/2012/11/symfony2-contact-bundle-part-3/][3]

## Composer configuration
In order for our bundle to be downloaded with composer, we need to create a composer.json file in the root of our bundle.  This will allow us to tell composer details about our bundle including its name, who wrote it and any dependencies it has (like the Symfony2 framework!):

``` js
//composer.json
{
    "name": "savvy/contact-bundle",
    "type": "symfony-bundle",
    "description": "Extra awesome contact bundle for Symfony2",
    "keywords": ["contact", "bundle"],
    "homepage": "http://github.com/oxygenthinking/SavvyContactBundle",
    "license": "MIT",
    "authors": [
        {
            "name": "Luke Rotherfield",
            "email": "luke@savvycreativeuk.com"
        }
    ],
    "require": {
        "php": ">=5.3.2"
    },
    "require-dev": {
        "symfony/framework-bundle": ">=2.1,<=2.2-dev"
    },
    "autoload": {
        "psr-0": { "Savvy\\ContactBundle": "" }
    },
    "minimum-stability": "dev",
    "target-dir": "Savvy/ContactBundle"
}
```

The "name" value must be lowercase to work with packagist later on in the tutorial.
## Git and GitHub
[Git][4] and [GitHub][5] are an extremely important part of [our][6] development process. Version control is not only a really helpful development and collaboration tool, it is also vital for large projects. For a more in depth read on version control check this article out: [http://www.oss-watch.ac.uk/resources/versioncontrol][7].

Now that we have covered the importance of version control we can set up a git repository and a remote repository on github.  These will allow us to publish the package easily in a bit using [Packagist][9].

To set up a Git repository;

1. [download Git][10] for your operating system.  If you are using windows you will need [msysgit][8] instead.
2. Initialise a Git repository on the command line:
``` shell
    cd /path/to/your/symfony/src/Savvy/ContactBundle
    git init
```
3. Create a .gitignore file in your ContactBundle root, we will cover unit tests later but for now we will ignore the folder:
```
    #.gitignore
    Tests/*
```
4. We can now add our files to our Git repository and commit them so that they start getting tracked:
``` shell
git add .
git commit -m "Initial commit of the awesome Contact Bundle"
```

With our files now being tracked we can setup a GitHub remote repository and push the SavvyContactBundle to it.  You will need to sign up for a GitHub account if you do not already have one: [https://github.com/signup/free][11].

Once you have signed into GitHub you can create a new repository: https://github.com/new.   We will call our new repository "savvy-contact-bundle" and leave it checked as Public (if you use your own name, make sure it is all lowercase).  Click "Create repository" and you should be given a set of commands to push the Git repository from your machine to GitHub(substitute your company name):
``` shell
git remote add origin git@github.com:yourCompanyName/savvy-contact-bundle.git
git push -u origin master
```
After you have run these commands you should see the files get pushed to the GitHub repository.  Check the repository online to see if it has been updated.

## Packagist
"[Packagist][9] is the main [Composer][14] repository. It aggregates all sorts of PHP packages that are installable with Composer". Sign up for an account and then we can submit the ContactBundle so that it can be installed with Composer.

To submit our ContactBundle visit: [https://packagist.org/packages/submit][15] and paste your repository url into the form.  Hopefully if all goes well you will be asked to confirm submission. Submit!!!

Follow the GitHub Service Hook walkthrough here: [https://packagist.org/profile/][13] to set up your package to be auto updated by GitHub.

That's it, our Contact Bundle is now available for install via Composer!

## Installing the ContactBundle in a new Symfony2 project
So the ContactBundle is ready for installation, great news but how do we install it.  The installation process is actually very simple.  Set up a new Symfony2 project with Composer, if you don't remember how just check out the first tutorial: [http://blog.savvycreativeuk.com/2012/11/symfony2-contact-bundle-part-1/][1].

Once the new project is set up, open the composer.json file and add the savvy/contact-bundle as a dependency:
``` js
//composer.json
//...
"require": {
        //other bundles
        "savvy/contact-bundle": "dev-master"
```
Save the file and have composer update the project via the command line:
``` shell
php composer.phar update
```
Composer will now update all dependencies and you should see our bundle in the list:
``` shell
  - Installing savvy/contact-bundle (dev-master dca591e)
    Cloning dca591eed83fa6a99dcfb5bc0ebcbba637043a5f
```

Now just update the app/AppKernel.php and app/config/routing.yml to include our bundle, set the local translations like in [Part 2][2], clear the cache and update the schema:
``` php
//app/AppKernel.php
//...
    public function registerBundles()
    {
        $bundles = array(
            //Other bundles
            new Savvy\ContactBundle\SavvyContactBundle()
        );
```
```
#app/config/routing.yml
#...
contact:
    resource: "@SavvyContactBundle/Controller/"
    type:     annotation
    prefix:   /
```
``` shell
php app/console cache:clear
php app/console doctrine:schema:update --force
```

And there you have it, a Symfony2 Contact Bundle set up on packagist and installed into a new project with Composer. Easy or what!

Quick but important thanks to Jamie at Newbridge Green for his [article][12] that helped us move to Composer from deps.

If you have any questions or suggestions for the next step of our tutorial please [let us know][6].

Thanks for reading, peace out

Luke

[1]:  http://blog.savvycreativeuk.com/2012/11/symfony2-contact-bundle-part-1/
[2]:  http://blog.savvycreativeuk.com/2012/11/symfony2-contact-bundle-part-2/
[3]:  http://blog.savvycreativeuk.com/2012/11/symfony2-contact-bundle-part-3/
[4]:  http://git-scm.com/
[5]:  http://github.com
[6]:  http://savvycreativeuk.com
[7]:  http://www.oss-watch.ac.uk/resources/versioncontrol
[8]:  http://code.google.com/p/msysgit/downloads/list?q=full+installer+official+git
[9]:  https://packagist.org/
[10]: http://git-scm.com/downloads
[11]: https://github.com/signup/free
[12]: http://blog.newbridgegreen.com/writing-and-publishing-your-first-symfony2-bundle/
[13]: https://packagist.org/profile/
[14]: http://getcomposer.org/
[15]: https://packagist.org/packages/submit