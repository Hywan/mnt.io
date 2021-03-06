<?php

namespace {

from('Jekxyl')
-> import('Post.~');

from('Hoa')
-> import('File.Read')
-> import('File.Write')
-> import('File.Directory')
-> import('File.Finder')
-> import('Xyl.~')
-> import('Xyl.Interpreter.Html.~');

}

namespace Jekxyl\Builder {

class Builder {

  private $_posts = array();

  private $_router = null;

  public function __construct() {

    $this->_router = require 'hoa://Application/In/Router.php';
  }

  public function build() {

    $this->reset();
    echo '✔  Output folder prepared', "\n";

    $this->build_posts();
    echo '✔  Posts built', "\n";

    $this->build_index();
    echo '✔  Index built', "\n";
  }

  private function reset() {

    // Empty Out directory before generating
    if(file_exists('hoa://Application/Out')) {

      $out = new \Hoa\File\Directory('hoa://Application/Out');
      $out->delete();
    }
    \Hoa\File\Directory::create('hoa://Application/Out');
  }

  private function build_posts() {

    // Loop through the directory listing
    $finder = new \Hoa\File\Finder();
    $finder->in('hoa://Application/In/Posts/')
           ->files()
           ->name('#\.xyl$#')
           ->sort(function ( $a, $b ) {

                return -1 * strcmp($a->getPathname(), $b->getPathname());
           });

    foreach ($finder as $name) {

      $post = new \Jekxyl\Post($name);
      $post->render();

      $this->_posts[] = $post;
    }
  }

  private function build_index() {

    // Render the index
    $index =  new \Hoa\Xyl(
      new \Hoa\File\Read('hoa://Application/In/Layouts/Main.xyl'),
      new \Hoa\File\Write('hoa://Application/Out/index.html'),
      new \Hoa\Xyl\Interpreter\Html(),
      $this->_router
    );

    $posts = array();
    foreach($this->_posts as $post) {

      $posts[] = array(
        'title'     => $post->getTitle(),
        'url'       => $post->getOutputFilename(),
        'timestamp' => $post->getTimestamp(),
        'date'      => date('c', $post->getTimestamp())
      );
    }

    $data = $index->getData();
    $data->posts = $posts;

    $index->addOverlay('hoa://Application/In/Index.xyl');
    $index->render();
  }

}

}

namespace {

Hoa\Core\Consistency::flexEntity('Jekxyl\Builder\Builder');

}