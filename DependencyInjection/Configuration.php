<?php

namespace Vaszev\HandyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {

  private $defaultImage = null;
  private $docs = null;
  private $imageQuality = 70;
  private $imageVariations = [];



  /**
   * Generates the configuration tree builder.
   * @return TreeBuilder $builder The tree builder
   */
  public function getConfigTreeBuilder() {
    $builder = new TreeBuilder();
    $rootNode = $builder->root('vaszev_handy');
    $rootNode
        ->children()
        ->variableNode('default_image')->defaultValue($this->defaultImage)->end()
        ->variableNode('docs')->defaultValue($this->docs)->end()
        ->variableNode('image_quality')->defaultValue($this->imageQuality)->end()
        ->variableNode('image_variations')->defaultValue($this->imageVariations)->end()
        ->end();

    return $builder;
  }
}
