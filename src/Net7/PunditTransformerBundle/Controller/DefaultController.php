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
//        $task->setPageContent('');
//        $task->setOutput('');

        $em = $this->getDoctrine()->getManager();
        $em->persist($task);
        $em->flush();

        $uri = $this->generateUrl('net7_pundit_transformer_status', array('taskId' => $task->getId()));


        $response = new Response('', 202, array());

        $response->headers->set('Location', $uri);

        return  $response;

    }

    /**
     * @param $taskId
     * @return Response
     * Check the status of a task, and reply with the result (if ended), the error (if an error was shipped) or a
     * trans:Processing if still running
     */
    public function statusAction($taskId){

        $em = $this->getDoctrine()->getManager();
        $task = $em->find('Net7PunditTransformerBundle:Task', $taskId);

        switch ($task->getStatus()){
            case \Net7\PunditTransformerBundle\Entity\Task::ENDED_STATUS:


                $content = $task->getOutput();
                break;

            case \Net7\PunditTransformerBundle\Entity\Task::STARTED_STATUS:

                \EasyRdf_Namespace::set('trans', 'http://vocab.fusepool.info/transformer#');

                $graph = new \EasyRdf_Graph();
                $graph->add(' ','trans:status', 'trans:Processing');
                $content = $graph->serialise('turtle');
                break;

            default:
            case \Net7\PunditTransformerBundle\Entity\Task::ERROR_STATUS:
                $content = "";
                break;


        }

        $statusCode = '202';

        $contentType =  array('content-type' => 'text/turtle');

        return new Response($content,$statusCode, $contentType);
    }

    /**
     * @param $taskId
     * @return Response
     *
     * Invoke Pundit with the content saved in the task passed as a parameter
     */
    public function showAction($taskId){

        $em = $this->getDoctrine()->getManager();
        $task = $em->find('Net7PunditTransformerBundle:Task', $taskId);

        $input = $task->getInput();


        $scraper = new \Net7\PunditTransformerBundle\FusepoolScraper($input);
        $page= $scraper->getContent();

        echo $page;

        die();


    }

    public function saveFromPunditAction(){


        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body, true);


        $punditContent = $data['punditContent'];
        $html = htmlspecialchars_decode($data['punditPage']);
        $asBaseUrl = $data['annotationServerBaseURL'];
        $apiUrl = $asBaseUrl . 'api/open/metadata/search?scope=all&query={"resources":["' . $punditContent . '"]}';

//    $annotations = callCURL($apiUrl, 'dealWithAnnotations');

        $annotations = new \EasyRdf_Graph($apiUrl);
        $annotations ->load();

        echo $annotations->serialise('turtle');
//    echo " \r\n ------  DETAILS ------ \r\n";
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
