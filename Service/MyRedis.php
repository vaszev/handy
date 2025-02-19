<?php

namespace Vaszev\HandyBundle\Service;

use Predis\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;


class MyRedis {

  const LIFETIME = 21600; // 6 hours
  const EXCLUDE_USER = '_noUserData_';
  const ITERATION_POOL = 3000;  // how much keys will be checked at once != results

  /** @var  $containerInterface ContainerInterface */
  protected $containerInterface;
  /** @var  $token TokenStorage */
  protected $token;
  /** @var $redis Client */
  protected $redis;

  private $compressLevel = 5; //  Can be given as 0 for no compression up to 9 for maximum compression



  /**
   * MyRedis constructor.
   * @param ContainerInterface $containerInterface
   * @param TokenStorage $token
   */
  public function __construct(ContainerInterface $containerInterface, TokenStorageInterface $token) {
    $this->token = $token;
    $this->containerInterface = $containerInterface;
    $this->redis = new Client([
        'scheme'     => 'tcp',
        'host'       => $containerInterface->getParameter('vaszev_handy.redis_host'),
        'port'       => $containerInterface->getParameter('vaszev_handy.redis_port'),
        'persistent' => true,
    ]);
  }



  /**
   * @return int
   */
  public function getCompressLevel(): int {
    return $this->compressLevel;
  }



  /**
   * @param int $compressLevel
   * @return MyRedis
   */
  public function setCompressLevel(int $compressLevel): MyRedis {
    if ($compressLevel < 0) {
      $compressLevel = 0;
    } elseif ($compressLevel > 9) {
      $compressLevel = 9;
    }
    $this->compressLevel = $compressLevel;

    return $this;
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
   * @param $key
   * @return int|null
   */
  public function getTTL($key) {
    try {
      if (!$this->isEnabled()) {
        throw new \Exception('redis not enabled, check the "redis" parameter in config files');
      }
      if (!$this->redis->isConnected()) {
        $this->redis->connect();
      }
      $key = $this->keyPrefix($key);
      $ttl = (int)$this->redis->ttl($key);
      if ($ttl <= 0) {
        throw new \Exception('redis ttl expired');
      }

      return $ttl;
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
      $data = gzdeflate(serialize($data), $this->getCompressLevel());
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



  public function isKeyExists($key): ?bool {
    try {
      if (!$this->isEnabled()) {
        throw new \Exception('redis not enabled, check the "redis" parameter in config files');
      }
      if (!$this->redis->isConnected()) {
        $this->redis->connect();
      }
      $ret = $this->redis->exists($key);

      return (bool)$ret;
    } catch (\Exception $e) {
      // error
      return false;
    }
  }



  public function countKeysByPrefix($prefix): ?int {
    try {
      if (!$this->isEnabled()) {
        throw new \Exception('redis not enabled, check the "redis" parameter in config files');
      }
      if (!$this->redis->isConnected()) {
        $this->redis->connect();
      }
      $iterator = null;
      $count = 0;
      do {
        [$iterator, $keys] = $this->redis->scan($iterator, ['MATCH' => $prefix . ':*', 'COUNT' => self::ITERATION_POOL]);
        $count += count($keys);
      } while ($iterator != 0);

      return $count;
    } catch (\Exception $e) {
      // error
      return null;
    }
  }



  /**
   * @param string $key
   * @param bool $more
   * @return false|int
   */
  public function deleteKeys(string $key, $more = false) {
    try {
      if (!$this->isEnabled()) {
        throw new \Exception('redis not enabled, check the "redis" parameter in config files');
      }
      if (!$this->redis->isConnected()) {
        $this->redis->connect();
      }
      if ($more) {
        $iterator = null;
        do {
          [$iterator, $keys] = $this->redis->scan($iterator, ['MATCH' => $key . ':*', 'COUNT' => self::ITERATION_POOL]);
          if ($keys) {
            $this->redis->del($keys); // push a limited size array to "del" method
          }
        } while ($iterator != 0);
      } else {
        return $this->redis->del($key);
      }
    } catch (\Exception $e) {
      // error
      return false;
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
        $response = $redis->scan($cursor, ['COUNT' => self::ITERATION_POOL]);
        $cursor = $response[0];
        $keys = $response[1];
        foreach ($keys as $key) {
          $matches = false;
          foreach ($allowedPrefixes as $prefix) {
            if (substr($key, 0, strlen($prefix)) === $prefix) {
              $matches = true;
              break;
            }
          }
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
