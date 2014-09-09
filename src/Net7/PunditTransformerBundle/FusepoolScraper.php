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
    public function __construct($data) {
        $this->data = $data;
        $this->retrievePunditContentDefault();
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

        if ($dom === false) {

            foreach (libxml_get_errors() as $error) {
                echo "\t", $error->message;
            }

        } else {

            $html = (string)current($dom->xpath('//rdf:RDF/fp:htmlContainer/fp:html'));
            if ($html) {
                return $html;
            } else {

                $dom = new \DOMDocument("1.0", "utf-8");
                libxml_use_internal_errors(true);
                if (!$dom->loadHTML($data)) {
                    foreach (libxml_get_errors() as $error) {
                        var_dump($error);
                    }
                    libxml_clear_errors();
                }
                return $dom->saveHTML();
            }


        }

    }


    public function doFirstTransformations(){
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

        return   md5($this->data);

    }

    private function retrievePunditContentDefault() {

        $punditAboutCode = 'http://purl.org/fp3/punditcontent-' . $this->doFirstTransformations();

        if (preg_match('%class="pundit-content"%',$this->punditContent)){
            $this->punditContent =
                preg_replace('%<body%','<body data-ng-app="Pundit2" ',$this->punditContent);
        }
        else {
            $this->punditContent =
                preg_replace('%<body([^>]*)>%s','<body data-ng-app="Pundit2" $1><div class="pundit-content" about="'.$punditAboutCode.'">',$this->punditContent);
            $this->punditContent =
                preg_replace('%</body>%','</div></body>',$this->punditContent);
        }

        $this->savedHTML = $this->punditContent;

        // ==================================
        // Add JS and CSS to end of HEAD
        // ==================================

        //          <link rel="stylesheet" href="/feedthepundit/src/css/feed.css" type="text/css">


        $punditCode = <<<EOF
          <link rel="stylesheet" href="/pundit2/feed.css" type="text/css">
          <link rel="stylesheet" href="/pundit2/pundit2.css" type="text/css">
          <script src="/pundit2/libs.js" type="text/javascript" ></script>
          <script src="/pundit2/pundit2.js" type="text/javascript" ></script>
          <script src="/fusepool_conf.js" type="text/javascript" ></script>
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