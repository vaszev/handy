<?php

namespace Vaszev\HandyBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class VaszevHandyExtension extends Extension implements PrependExtensionInterface {

  public function load(array $configs, ContainerBuilder $container) {
    $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

    $loader->load('services.yml');
    $config = $this->processConfiguration(new Configuration(), $configs);
    /**
     * default_image, docs, image_variations['small'->[150,350]]
     */
    foreach ($config as $key=>$val) {
      $container->setParameter('vaszev_handy.'.$key, $val);
    }
  }



  public function prepend(ContainerBuilder $container) {
    $configs = $container->getExtensionConfig($this->getAlias());
    $config = $this->processConfiguration(new Configuration(), $configs);
  }



  public function getAlias() {
    return 'vaszev_handy';
  }
}