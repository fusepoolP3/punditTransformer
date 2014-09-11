<?php

namespace Net7\PunditTransformerBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


class DefaultController extends Controller
{
    /**
     * The basic information method.
     * Display basic info on the transformer
     * @return Response
     */
    public function infoAction()
    {

        $punditTransformerURI = 'http://temporary.punditTransformer.uri.com/punditTransformer/';

        \EasyRdf_Namespace::set('trans', 'http://vocab.fusepool.info/transformer#');
        \EasyRdf_Namespace::set('dct', 'http://purl.org/dc/terms/');

        $graph = new \EasyRdf_Graph();

        $r = $graph->resource($punditTransformerURI, 'trans:Transformer');


        $r->add('dct:title', 'Pundit Transformer');
        $r->add('dct:description', 'Gets HTML/RDF documents and let the user add annotations on it');
        $r->add('trans:supportedInputFormat', 'text/html');
        $r->add('trans:supportedInputFormat', 'text/turtle');
        $r->add('trans:supportedOutputFormat', 'text/turtle');

        return new Response($graph->serialise('turtle'), '200', array('Content-type' => 'text/turtle'));

    }

    /**
     * Get the document (either HTML of RDF containing the HTML in a literal, and prepare the stuff for pundit.
     * @return string
     */
    public function startAction(){

        $request = Request::createFromGlobals();
        $document = $request->request->get('data');

        $task = new \Net7\PunditTransformerBundle\Entity\Task();
        $task->setInput($document);

        $em = $this->getDoctrine()->getManager();
        $em->persist($task);
        $em->flush();

        $statusUrl = $this->generateUrl('net7_pundit_transformer_status', array('token' => $task->getToken()));


        $content = '';
        // TEMPORARY HACK, until we have a UI layer, we just show the url to be used in the browser
        $content = "USE THIS URL TO ANNOTATE THIS DOCUMENT IN YOUR BROWSER: " .  $this->generateUrl('net7_pundit_transformer_show', array('token' => $task->getToken()), true) . " \r\n\r\n";

        $response = new Response($content, 202, array());

        $response->headers->set('Location', $statusUrl);

        return  $response;

    }

    /**
     * @param $token
     * @return Response
     * Check the status of a task, and reply with the result (if ended), the error (if an error was shipped) or a
     * trans:Processing if still running
     */
    public function statusAction($token){

        $em = $this->getDoctrine()->getManager();
        $task = $em->getRepository('Net7\PunditTransformerBundle\Entity\Task')->findOneBy(array('token' => $token));

        switch ($task->getStatus()){
            case \Net7\PunditTransformerBundle\Entity\Task::ENDED_STATUS:
                $content = $task->getOutput();
                $statusCode = '200';
                break;

            case \Net7\PunditTransformerBundle\Entity\Task::STARTED_STATUS:

                \EasyRdf_Namespace::set('trans', 'http://vocab.fusepool.info/transformer#');
                $graph = new \EasyRdf_Graph();
                $graph->add(' ','trans:status', 'trans:Processing');
                $content = $graph->serialise('turtle');
                $statusCode = '202';
                break;

            default:
            case \Net7\PunditTransformerBundle\Entity\Task::ERROR_STATUS:
                $statusCode = '400';
                $content = "";
                break;


        }



        $contentType =  array('content-type' => 'text/turtle');

        return new Response($content,$statusCode, $contentType);
    }

    /**
     * @param $token
     * @return Response
     *
     * Invoke Pundit with the content saved in the task passed as a parameter
     */
    public function showAction($token){

        $em = $this->getDoctrine()->getManager();
//        $task = $em->find('Net7PunditTransformerBundle:Task', $token);
        $task = $em->getRepository('Net7\PunditTransformerBundle\Entity\Task')->findOneBy(array('token' => $token));

        $input = $task->getInput();


        $scraper = new \Net7\PunditTransformerBundle\FusepoolScraper($input);
        $page= $scraper->getContent();



        echo $page;

        die();


    }

    public function saveFromPunditAction(){

        $request = Request::createFromGlobals();
        $http_referer = $request->server->get('HTTP_REFERER');
        $token = substr($http_referer, strpos($http_referer , '/show/') + strlen('/show/'));

        if (!$token){
         die();
        }

        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body, true);


        $punditContent = $data['punditContent'];
        $html = htmlspecialchars_decode($data['punditPage']);
        $asBaseUrl = $data['annotationServerBaseURL'];
        $apiUrl = $asBaseUrl . 'api/open/metadata/search?scope=all&query={"resources":["' . $punditContent . '"]}';


        echo $apiUrl;
        die();

        $annotations = new \EasyRdf_Graph($apiUrl);
        $annotations ->load();

        echo $annotations->serialise('turtle');
        foreach($annotations->resources() as $key => $resource){


//        echo $resource->serialize('turtle');
//        echo $key . "     ";
            $annotationId = $resource->get('<http://purl.org/pundit/ont/ao#id>');

            // fam:selector
            $target = $resource->get('<http://www.openannotation.org/ns/hasTarget>');

            echo "\r\n --- Target: " . $target . " --- \r\n";
            // fam:extracted-from
            $pageContent = $resource->get('<http://purl.org/pundit/ont/ao#hasPageContext>');
            echo "\r\n content -> " . $pageContent . " --- \r\n";;

            echo "\r\n --- Annotation: " . $key . " ---\r\n";

            $metadataUrl = $asBaseUrl . 'api/open/annotations/' . $annotationId . '/metadata';
            $graphUrl = $asBaseUrl . 'api/open/annotations/' . $annotationId . '/graph';
            $itemsUrl = $asBaseUrl . 'api/open/annotations/' . $annotationId . '/items';

            $md = new \EasyRdf_Graph($metadataUrl);
            $gr = new \EasyRdf_Graph($graphUrl);
            $it = new \EasyRdf_Graph($itemsUrl);

            $md->load();
            $gr->load();
            $it->load();
            echo "\r\n -- Metadata: \r\n";
            echo $md->serialise('turtle');
            echo "\r\n -- Graph: \r\n";
            echo $gr->serialise('turtle');
            echo "\r\n -- Items: \r\n";
            echo $it->serialise('turtle');

            echo " \r\n ------------ \r\n";
        }



//    echo $graph;

        die();

        die();
    }
}
