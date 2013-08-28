<?php

namespace Kindle;

include_once dirname(__FILE__)."/vendor/autoload.php";
use Rain\Tpl;

class Periodical{

    private $title;
    private $creator;
    private $publisher;
    private $subject;
    private $description;
    private $filename;

    private $directory;
    private $outputFolder;
    private $debug = false;
    private $shell = true;
    private $data;

    public $output;

    function __construct($data)
    {

        try{            

            // config
            $config = array(
                    "tpl_dir" => dirname(__FILE__)."/tpl/",
             );

            Tpl::configure( $config );            

            if(!empty($data['debug']))
                $this->setDebug($data['debug']);

            if(!empty($data['outputFolder'])){
                $this->setOutputFolder($data['outputFolder']);
            }else{
                 throw new Exception("You must specify a outuput directory");
            }

            if(!empty($data['shell']))
                $this->setShell($data['shell']);

            // Create folders
            $this->createAppDirectory();
            $this->createDirectory();

        } catch(Exception $e){
            $this->debug($e->getMessage());
        }

    }

    private function debug($a){
        echo "<pre>";
        var_dump($a);
        echo "</pre>";
    }

    private function number_pad($number,$n){
        return str_pad((int) $number,$n,"0",STR_PAD_LEFT);
    }

    private function process_string($str){

        $str = strip_tags($str, '<p><a><br>');

        $str = preg_replace('/ +/', ' ', $str);
        $str = trim($str);
        $str = utf8_encode($str);

        return $str;
    }

    private function writeUTF8File($filename, $content)
    {
        $f = fopen($filename, "w");
        fwrite($f, pack("CCC", 0xef, 0xbb, 0xbf));
        fwrite($f, $content);
        fclose($f);
    }

    private function createAppDirectory()
    {
        if (!file_exists($this->outputFolder)) {
            mkdir($this->outputFolder, 0777);
        }
    }

    private function createDirectory()
    {

        $dirname = date('YmdHis') . rand(123123, 45345345);

        if (!file_exists($this->outputFolder.'/' . $dirname)) {

            if ($this->debug == TRUE)
                echo "Creating the folder ".$this->outputFolder."/" . $dirname . "\n";

            mkdir($this->outputFolder.'/' . $dirname, 0777);
            $this->directory = $this->outputFolder . '/' . $dirname;

        }
    }

    private function removeDirectory()
    {

        if (file_exists($this->directory)) {
            rmdir($this->directory);
        }

    }

    // Getters and Setters

    public function setMeta($data)
    {

        try{

            $keys = array('title', 'creator', 'publisher', 'subject', 'description');

            foreach($keys as $key){

                if(!array_key_exists($key, $data)){
                    throw new Exception("You must specify the ".$key);
                }

            }

            $this->title = $data['title'];
            $this->creator = $data['creator'];
            $this->publisher = $data['publisher'];
            $this->subject = $data['subject'];
            $this->description = $data['description'];

            $this->setFilename('Ebook');

        }catch (Exception $e){
            $this->debug($e->getMessage()) ;
        }

    }

    public function setFilename($file)
    {
        $this->filename = $file;
    }

    public function getFilename()
    {
        return $file_name = $this->directory . '/' . $this->filename . '.mobi';
    }

    public function setContent($data)
    {

        $this->data = $data;
        $this->createArticles();
        $this->createContents();
        $this->createOPF();
        $this->createNCX();

        $output = $this->createMOBI();
        $this->deleteTempFiles();

        return $output;

    }

    private function setOutputFolder($folder){
        $this->outputFolder = $folder;
    }

    private function setShell($shell){

        try{
            if(is_bool($shell)) {
                $this->shell = $shell;
            }else{
                throw new Exception("Shell argument must be boolean");
            }
        } catch(Exception $e){
            $this->debug($e->getMessage());
        }

    }

    private function setDebug($debug){

        try{
            if(is_bool($debug)) {
                $this->debug = $debug;
            }else{
                throw new Exception("Debug argument must be boolean");
            }
        } catch(Exception $e){
            $this->debug($e->getMessage());
        }

    }

    private function getTemplate($template, $data){

        $tpl = new Tpl;

        foreach($data as $key=>$value){
            $tpl->assign($key, $value);
        }

        return $tpl->draw($template, $return_string=true);

    }

    private function createOPF()
    {

        $classdata = $this->data;
        $count = 0;

        foreach ($classdata as $notebooks) {
            $count = $count + count($notebooks['content']);
        }

        $my_file = $this->directory . '/content.opf';
        $handle = fopen($my_file, 'w') or die('Cannot open file:  ' . $my_file);

        $manifest = '';

        for ($i = 1; $i <= $count; $i++):

            $manifest .= "<item href='" . $this->number_pad($i, 5) . ".html' media-type='application/xhtml+xml' id='item-" . $this->number_pad($i, 5) . "'/>\n";

        endfor;

        $items_ref = '';

        for ($i = 1; $i <= $count; $i++):

            $items_ref .= "<itemref idref='item-" . $this->number_pad($i, 5) . "'/>\n";

        endfor;

        $templateData = array(
           'title'          => $this->title,
           'creator'        => $this->creator,
           'publisher'      => $this->publisher,
           'subject'        => $this->subject,
           'description'    => $this->description,
           'date'           => date('Y-m-d'),
           'items_manifest' => $manifest,
           'items_ref'      => $items_ref,
           'identifier'     => rand(100, 1000).date('YmdHis')
        );

        $data = $this->getTemplate("content_opf", $templateData);

        fwrite($handle, $data);
        fclose($handle);

    }

    private function createNCX()
    {

        $classdata = $this->data;
        $play_order = 0;

        $my_file = $this->directory . '/nav-contents.ncx';
        $handle = fopen($my_file, 'w') or die('Cannot open file:  ' . $my_file);

        $sections = '';
        $i = 0;
        $z = 1;

        foreach ($classdata as $notebook):

            $play_order++;

            $articles = '';
            $section_first = $z;
            $section_play_order = $play_order;

            foreach ($notebook['content'] as $note):

                $play_order++;

                $article = $this->getTemplate('article_ncx', array(
                    'id'    =>   $this->number_pad($z, 5),
                    'play_order'    => $play_order,
                    'title'         => $note['title'],
                    'description'   => $this->getDescription($note['content']),
                    'author'        => $this->creator
                ));

                $articles .= $article;

                $z++;

            endforeach;

            $section = $this->getTemplate('section_ncx', array(
                'section_id'    =>  "section-" . $i,
                'play_order'    => $section_play_order,
                'section_title' => $notebook['name'],
                'section_first' => $this->number_pad($section_first, 5),
                'articles'      => $articles
            ));

            $section = str_replace('{articles}', $articles, $section);
            $sections .= $section;

            $i++;

        endforeach;

        $nc_content = $this->getTemplate('nav-contents_ncx', array(
            'title' => $this->title,
            'creator' => $this->creator,
            'sections' => $sections
        ));

        fwrite($handle, $nc_content);
        fclose($handle);

    }

    private function createContents()
    {

        $classdata = $this->data;

        $my_file = $this->directory . '/contents.html';
        $handle = fopen($my_file, 'w') or die('Cannot open file:  ' . $my_file);

        $sections = '';
        $i = 0;
        foreach ($classdata as $notebook):

            $sections .= '<h4>' . $notebook['name'] . '</h4>';
            $sections .= '<ul>';

            foreach ($notebook['content'] as $note):
                $i++;
                $sections .= '<li><a href="' . $this->number_pad($i, 5) . '.html">' . $note['title'] . '</a></li>';
            endforeach;

            $sections .= '</ul>';

        endforeach;

        $data = $this->getTemplate('contents', array('sections' => $sections));

        fwrite($handle, $data);
        fclose($handle);

    }

    private function createArticles()
    {

        $classdata = $this->data;

        $i = 0;
        foreach ($classdata as $notebook):

            foreach ($notebook['content'] as $note):

                $i++;

                $my_file = $this->directory . '/' . $this->number_pad($i, 5) . '.html';
                //$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);

                $content = $note['content'];
                $description = $this->getDescription($note['content']);

                $data = $this->getTemplate('article', array(
                   'title'          =>  $note['title'],
                   'creator'        => $this->creator,
                   'description'    =>  strip_tags($description, '<p><a><br>'),
                   'content'        =>  strip_tags($content, '<p><a><br><hr><ul><li><ol><h1><h2><h3><h4><h5><h6>')
                ));

                $this->writeUTF8File($my_file, $data);

                if (!file_exists($my_file)) {
                    die('Error gerando o arquivo ' . $my_file);
                }

            endforeach;

        endforeach;

    }

    private function createMOBI()
    {

        $generate = 'cd ' . $this->directory . '; kindlegen -c2 content.opf -o ' . $this->filename . '.mobi';

        if($this->shell == true){
            $output = shell_exec($generate . " 2>&1");
        }else{
            exec($generate, $output);
        }

        if ($this->debug == TRUE):
            echo $generate . "<br /><br />";
            echo "<pre>" . $output . "</pre>";
        endif;

        return $output;

    }

    private function getDescription($description)
    {

        $description = strip_tags($description);
        $description = str_replace("\n", " ", $description);
        $description = str_replace("  ", " ", $description);
        $description = str_replace("	", " ", $description);
        $description = str_replace(">", "", $description);
        $description = str_replace("<", "", $description);
        $description = substr($description, 0, 150);

        return $this->process_string($description);

    }

    private function deleteTempFiles(){

        $files = glob($this->directory . '/*');
        foreach ($files as $file) {
            if (is_file($file) && !strpos($file, ".mobi"))
                unlink($file);
        }

    }

    public function downloadFile()
    {

        $file_url = 'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"] . $this->getFilename();
        header('location:' . $file_url);

    }

    public function deleteFile()
    {

        unlink($this->getFilename());
        rmdir($this->directory);

    }


}

?>