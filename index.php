<?php
//wordpress permalink compatibility
if (isset($_GET['p'])) {header("Location: old/".$_GET['p']); die();}
require_once 'config.php';
require_once 'prefs.php';
require_once 'Markdown.php';
require_once 'functions.php';
use \Michelf\Markdown;
$requested_file = str_replace('/'.$folder, "", $_SERVER['REQUEST_URI']);
if ($requested_file != '' && !file_exists('content/'.$requested_file.".md")) {
    header("HTTP/1.0 404 Not Found");
    $requested_file = '404';
}
if ($requested_file == '') {
    $is_front_page = true;
    $oldposts = glob('content/old/*.md');
    $posts = glob('content/posts/*.md');
    $posts = array_merge($oldposts, $posts);
    foreach ($posts as $post_file){
        //ignore symlinks (this way one can use symlinks to keep old url's around
        //and move actual post files)
        if (is_link($post_file)) continue;
        $post = get_post($post_file);
        if ($post['Status']!='Draft'){
            $post_html = Markdown::defaultTransform($post['text']);
            if (isset($post['Date']))
                $timestamp = strtotime($post['Date']);
            if (!isset($newposts[$timestamp])) $key = $timestamp; else $key = $timestamp+1;

            $newposts[$key]=array();
            $newposts[$key]['file']=str_replace("content/","",str_replace(".md", '', $post_file));
            $newposts[$key]['meta']=$post;
            $newposts[$key]['html']=$post_html;
            $newposts[$key]['excerpt']=truncate(strip_tags($post_html,"<p><a><pre><code>"),$excerpt_length);
        }
    }
    unset($post);
    $posts = $newposts;
    ksort($posts);
    $posts = array_reverse($posts, true);
} else {
    $is_front_page = false;

    $post_file = 'content/'.$requested_file.'.md';
    $post = file_get_contents($post_file);
    $post = get_post($post_file);

    $post_html = Markdown::defaultTransform($post['text']);
    //we are allowing 'real' paths so that both the site and IDE's render content ok
    $post_html = preg_replace('#src=("?)\.\./(?!content/)#', 'src=$1../content/', $post_html);
}

$pages = glob('content/pages/*.md');
$newpages = array();
foreach ($pages as $page){
    $pagemeta = parseMeta($page);
    $newpages[$pagemeta['title']]=str_replace("content/","",str_replace(".md", '', $page));
}
$pages = $newpages;

$themeName = 'themes/'.$theme;
$theme = "/".$folder.'themes/'.$theme;

include "$themeName/template.php";
