#`Pundit` Transformer

##Introduction:

The PunditTransformer brings the pundit annotation tool (http://thepund.it) to the Fusepool P3 Project.

It implements the Asynchronous Transformer APIs and the User Interaction Request APIs to manage its workflow.

When a POST request is sent to the transformer, the request content is stored in a DB table and a new Task is created and
an Interaction Request is issued to the configured Interaction Request Container.

The user can access the Pundit client and annotate the content sent with the initial request at the URL included in the
Interaction Request, which will open up the text inside the Pundit client, letting the user create new annotations, and send
 back those annotations using the Fusepool Annotation Model (FAM) as an output.

## Compiling and Running

The PunditTransformer is a Symfony 2 PHP application and as such it needs a web server to run it (e.g. Apache) as well as
an access to a MySQL database.

### Composer initialization

As with many Symfony 2 application, after cloning the repository you need to run the command `composer install` to download all the dependencies.
The `composer install` also allows you to configure the access to the MySQL database by asking you all the configuration data.

### DB setup
After this step you'll need to actually create the database:

* Create the database by running:

    `php app/console doctrine:database:create`

* In case of need, to drop and recreate the DB use

`php app/console doctrine:database:drop --force`

`php app/console doctrine:database:create`


* Fix permissions on logs and cache dir (use better/safe permissions in production)

`sudo chmod -R 777 cache/`

`sudo chmod -R 777 logs/`

### Configuration
The PunditTransformer makes use of the User Interaction Request APIs and communicates with a User Interaction Request Container, which must be configured.

Also we need to configure the URL at which the transformer is reached, this is used in the construction of the responses.

To set the tontainer and the transformer URLs, edit the file:

`src/Net7/PunditTransformerBundle/Resources/config/services.php`

and configure the two parameters as in the following example:

    $container->setParameter('IRURL', 'http://sandbox.fusepool.info:8181/ldp/ir-ldpc');

    $container->setParameter('TransformerUrl', 'http://pundittransformer.example.org/');


## Usage

Once running, you can send POST requests to the container including the text to annotate as a request body, for instance via curl as in the following example:

    $ curl -v -d@../pundit/example.rdf pundittransformer.example.org

which will reply with something like:

    < HTTP/1.1 201 Created
    < Location: /status/54d4ed8decaef
    < Vary: Accept-Encoding
    < Content-Type: text/html; charset=UTF-8

The above POST request triggers a User Interaction request call, creates a new task and returns the Location header, which tells us where we can check for the status of our task (http://pundittransformer.example.org/status/54d4ed8decaef in this example)
A curl request to it will tell us:

    @prefix trans: <http://vocab.fusepool.info/transformer#> .

    <> trans:status "trans:Processing"

meaning it is still in process.

By accessing the User Interaction request container configured, we will find the task we've just created with the URL to follow in order to start annotating the document with the pundit client.
 When the annotation process is finished a further request to the status URL above will return the turle document containing all the annotations the user created in Pundit using the FAM model.





