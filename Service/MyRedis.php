<?php

namespace Vaszev\HandyBundle\Service;

use Predis\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;


class MyRedis {

  const LIFETIME = 21600; // 6 hours
  const EXCLUDE_USER = '_noUserData_';

  /** @var  $containerInterface ContainerInterface */
  protected $containerInterface;
  /** @var  $token TokenStorage */
  protected $token;
  /** @var $redis Client */
  protected $redis;



  /**
   * MyRedis constructor.
   * @param ContainerInterface $containerInterface
   * @param TokenStorage $token
   */
  public function __construct(ContainerInterface $containerInterface, TokenStorageInterface $token) {
    $this->token = $token;
    $this->containerInterface = $containerInterface;
    $this->redis = new Client([
        'scheme' => 'tcp',
        'host'   => $containerInterface->getParameter('vaszev_handy.redis_host'),
        'port'   => $containerInterface->getParameter('vaszev_handy.redis_port'),
    ]);
  }



  private function isEnabled() {
    $enabled = (boolean)$this->containerInterface->getParameter('vaszev_handy.redis');

    return $enabled;
  }



  private function keyPrefix($key) {
    try {
      $pos = strpos($key, self::EXCLUDE_USER);
      if ($pos !== false) {
        throw new \Exception('user data exclude');
      }
      $token = $this->token->getToken();
      if (empty($token)) {
        throw new \Exception('invalid token');
      }
      $user = $token->getUser();
      if (empty($user)) {
        throw new \Exception('invalid user');
      }
      $key = 'user_' . $token->getUsername() . '_' . $key;
    } catch (\Exception $e) {
      // error, no modifications needed
    }

    return $key;
  }



  public function getFast($key) {
    try {
      if (!$this->isEnabled()) {
        throw new \Exception('redis not enabled, check the "redis" parameter in config files');
      }
      if (!$this->redis->isConnected()) {
        $this->redis->connect();
      }
      $key = $this->keyPrefix($key);
      $data = $this->redis->get($key);
      if (empty($data)) {
        throw new \Exception('cache entry empty');
      }

      return unserialize(gzinflate($data));
    } catch (\Exception $e) {
      return null;
    }
  }



  /**
   * alias for getFast
   * @param $key
   * @return string|null
   */
  public function get($key) {
    return $this->getFast($key);
  }



  public function setFast($key, $data, $lifeTime = self::LIFETIME) {
    try {
      if (!$this->isEnabled()) {
        throw new \Exception('redis not enabled, check the "redis" parameter in config files');
      }
      if (!$lifeTime) {
        throw new \Exception('zero lifetime');
      }
      if (!$this->redis->isConnected()) {
        $this->redis->connect();
      }
      $key = $this->keyPrefix($key);
      $data = gzdeflate(serialize($data), 1);
      $this->redis->set($key, $data, 'EX', $lifeTime);
    } catch (\Exception $e) {
      // error
    }

    return null;
  }



  /**
   * alias for setFast
   * @param $key
   * @param $data
   * @param $section null
   * @param int $lifeTime
   * @return null
   */
  public function set($key, $data, $section = null, $lifeTime = self::LIFETIME) {
    return $this->setFast($key, $data, $lifeTime);
  }



  /**
   * @return mixed
   */
  public function flushDB() {
    try {
      if (!$this->isEnabled()) {
        throw new \Exception('redis not enabled, check the "redis" parameter in config files');
      }
      if (!$this->redis->isConnected()) {
        $this->redis->connect();
      }

      return $this->redis->flushdb();
    } catch (\Exception $e) {
      // error
      return false;
    }
  }



  /**
   * @return array
   */
  public function info(): array {
    try {
      if (!$this->isEnabled()) {
        throw new \Exception('redis not enabled, check the "redis" parameter in config files');
      }
      if (!$this->redis->isConnected()) {
        $this->redis->connect();
      }

      return $this->redis->info();
    } catch (\Exception $e) {
      // error
      return [];
    }
  }



  /**
   * @param string $keyPart
   * @return mixed
   */
  public function deleteAllKeysExceptLikeThis(string $keyPart = 'PHPREDIS_SESSION') {
    try {
      if (!$this->isEnabled()) {
        throw new \Exception('redis not enabled, check the "redis" parameter in config files');
      }
      if (!$this->redis->isConnected()) {
        $this->redis->connect();
      }
      $trashKeys = [];
      foreach ($this->redis->keys('*') as $key) {
        if (strpos($key, $keyPart) === false) {
          $trashKeys[] = $key;
        }
      }
      if ($trashKeys) {
        return $this->redis->del($trashKeys);
      }

      return false;
    } catch (\Exception $e) {
      // error
      return false;
    }
  }



  function deleteKeysWithoutPrefixes(array $allowedPrefixes = []) {
    try {
      if (!$this->isEnabled()) {
        throw new \Exception('redis not enabled, check the "redis" parameter in config files');
      }
      if (empty($allowedPrefixes)) {
        throw new \Exception('allowed prefixes is empty');
      }
      if (!$this->redis->isConnected()) {
        $this->redis->connect();
      }
      $cursor = null;
      $keysToDelete = [];
      $redis = $this->redis;
      do {
        $response = $redis->scan($cursor, ['COUNT' => 1000]);
        $cursor = $response[0];
        $keys = $response[1];
        foreach ($keys as $key) {
          $matches = false;
          foreach ($allowedPrefixes as $prefix) {
            if (substr($key, 0, strlen($prefix)) === $prefix) {
              $matches = true;
              break;
            }          }
          if (!$matches) {
            $keysToDelete[] = $key;
          }
        }
      } while ($cursor != 0);
      if (!empty($keysToDelete)) {
        $redis->del(...$keysToDelete);
      }

      return true;
    } catch (\Exception $e) {
      // error
      return false;
    }
  }

}
