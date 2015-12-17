<?php

namespace Net7\PunditTransformerBundle;

class FusepoolScraper {


    private $data;
    private $savedHTML;
    private $punditContent ;


    private $Fp3Ns = 'http://vocab.fusepool.info/fp3#';
    private $RDFHtmlTag = 'html';
    private $RDFHtmlContainerTag = 'htmlContainer';


    /**
     * Public contructor gets the data to be annotated, either in HTML or RDF format (containing the HTML)
     * as a parameter
     */
    public function __construct($data, $token) {
        $this->data = $data;
        $this->retrievePunditContentDefault($token);
    }


    /**
     * Return the HTML of the page to be annotated.
     * @return mixed
     */
    public function getContent(){
        return $this->punditContent;
    }



    /**
     * Extracts the HTML from the input data.
     * It can be either the whole data var or, if the data is RDF, nested into some nodes of such RDF
     *
     *
     * @return mixed
     */

    private function extractHtmlFromData()
    {
        $data = $this->data;

        libxml_use_internal_errors(true);
        $dom = new \SimpleXMLElement($data, LIBXML_HTML_NOIMPLIED && LIBXML_NOXMLDECL);
        libxml_use_internal_errors(false);
        if ($dom === false) {

            foreach (libxml_get_errors() as $error) {
                echo "\t", $error->message;
            }

        } else {
            $dom->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
            $dom->registerXPathNamespace('fp', 'http://vocab.fusepool.info/fp3#');
            $html = $dom->xpath('//rdf:RDF/fp:htmlContainer/fp:html');
            if (is_array($html) && count($html)>0) {
                return (string)current($html);
            } else {

                $dom = new \DOMDocument("1.0", "utf-8");
                libxml_use_internal_errors(true);
                if (!$dom->loadHTML($data)) {
                    foreach (libxml_get_errors() as $error) {
                        var_dump($error);
                    }
                    libxml_clear_errors();
                }
                libxml_use_internal_errors(false);
                return $dom->saveHTML();
            }


        }

    }

    private function retrievePunditContentDefault($token) {
        $this->punditContent = $this->extractHtmlFromData();

        if (!$this->punditContent){
            self::abortToFP('Empty input');
        }

        // ==================================
        // Check if there's and HEAD section
        // ==================================
        $dom = new \DOMDocument();

        libxml_use_internal_errors(true);
        if(!$dom->loadHTML($this->punditContent)){
            foreach (libxml_get_errors() as $error) {
                var_dump($error);
            }
            libxml_clear_errors();
        }
        libxml_use_internal_errors(false);
        $headNodeList = $dom->getElementsByTagName('head');
        $l = $headNodeList->length;

        if($l == 0){
            // we need to add an head section to the HTML
            $this->punditContent =
                preg_replace('%<html>(.*)%s', '<html><head></head>$1', $this->punditContent);
        }
        // ====================================================
        //   Add data-app-ng="Pundit2" attribute to BODY TAG
        //   And a pundit-content
        // ====================================================
        $punditAboutCode = 'http://purl.org/fp3/punditcontent-' . $token;

        if (preg_match('%class="pundit-content"%',$this->punditContent)){
            $this->punditContent =
                preg_replace('%<body([^>]*)>%','<body $1><div data-ng-app="Pundit2"></div> ',$this->punditContent);

            $this->savedHTML =  preg_replace('%<body([^>]*)>%','<body $1><div></div> ',$this->punditContent);
        }
        else {
            $this->punditContent =
                preg_replace('%<body([^>]*)>%s','<body $1><div data-ng-app="Pundit2"></div><div class="pundit-content" about="'.$punditAboutCode.'">',$this->punditContent);
            $this->savedHTML =
                preg_replace('%<body([^>]*)>%s','<body $1><div></div><div class="pundit-content" about="'.$punditAboutCode.'">',$this->punditContent);
            $this->punditContent =
                preg_replace('%</body>%','</div></body>',$this->punditContent);
            $this->savedHTML =
                preg_replace('%</body>%','</div></body>', $this->savedHTML);
        }


        // ==================================
        // Add JS and CSS to end of HEAD
        // ==================================

        //          <link rel="stylesheet" href="/feedthepundit/src/css/feed.css" type="text/css">


        $punditCode = <<<EOF
          <link rel="stylesheet" href="/feed.css" type="text/css">
          <link rel="stylesheet" href="http://dev.thepund.it/download/client/last-beta/pundit2.css" type="text/css">
          <script src="http://dev.thepund.it/download/client/last-beta/libs.js" type="text/javascript" ></script>
          <script src="http://dev.thepund.it/download/client/last-beta/pundit2.js" type="text/javascript" ></script>
          <script src="/pundit_config" type="text/javascript" ></script>

EOF;

        $this->punditContent =
            preg_replace('%<head>(.*)</head>%s','<head>$1 '.$punditCode.'</head>',$this->punditContent);

        // save the HTML in an hidden place so to be able to send it back to the FP platform at the end of the process
        $this->punditContent =
            preg_replace('%</body>%s', '<div style="display:none" id="html-storage"><![CDATA[' .$this->savedHTML. ']]></div></body>', $this->punditContent);


    }

    /**
     * Quits the execution and get back to the Fusepool platform passing an error message
     *
     * @param $message - The message to be sent
     * TODO: ALL
     */

    public static function abortToFP($message = ''){
        // TODO: get the message, construct a result for the FP platform and ship it back
        die('ABORT TO FP' . $message);
    }




} 