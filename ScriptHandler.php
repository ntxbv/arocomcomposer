<?php

/**
 * @file
 * Contains \DrupalProject\composer\ScriptHandler.
 */

namespace DrupalProject\composer;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use Drupal\Core\Site\Settings;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

if (!defined('DRUPAL_ROOT')) {
  // Looping to find Drupal root folder.
  $current_dir = \dirname(__DIR__, 2) . '/web';
  while (!file_exists("$current_dir/index.php")) {
    $current_dir = \dirname($current_dir);
  }
  \define('DRUPAL_ROOT', $current_dir);
}
class ScriptHandler {

  public static function postInstall(Event $event) {
    self::applyBasePatches($event);
    self::createRequiredFiles($event);
    self::removeGitDirectories($event);
    self::clearRedisCache($event);

  }

  public static function postUpdate(Event $event) {
    self::applyBasePatches($event);
    self::createRequiredFiles($event);
    self::removeGitDirectories($event);
    self::clearRedisCache($event);

  }
  /**
   * Patch scaffolding files.
   *
   * @param \Composer\Script\Event $event
   *
   * @throws \Exception
   */

  protected static function applyBasePatches(Event $event) {


    # Create Scaffolds for patch logic
    if (!is_dir('./patches')) {
      mkdir('./patches', 0777, true);
    }
    if (!is_dir('./patches/base')) {
      mkdir('./patches/base', 0777, true);
    }
    if (!is_dir('./patches/core')) {
      mkdir('./patches/core', 0777, true);
    }
    if (!is_dir('./patches/libraries')) {
      mkdir('./patches/libraries', 0777, true);
    }
    if (!is_dir('./patches/modules')) {
      mkdir('./patches/modules', 0777, true);
    }
    if (!is_dir('./patches/themes')) {
      mkdir('./patches/themes', 0777, true);
    }
    file_put_contents('./patches/base.patches.json', '[
    "patches/base/default.settings.php.patch",
    "patches/base/robots.txt.patch",
    "patches/base/htaccess.patch"
    ]');
    file_put_contents('./patches/composer.patches.json', '{
  "patches": {
    "vendor/project": {
      "Patch title": "http://example.com/url/to/patch.patch"
    }
}
}');
    $source = '/var/www/robots.txt.patch';
    $destination = './patches/base/robots.txt.patch';
    if( !copy($source, $destination) ) {
      echo "File can't be copied! \n";
    }
    else {
      echo "robots.txt.patch has been copied! \n";
    }

    $source = '/var/www/htaccess.patch';
    $destination = './patches/base/htaccess.patch';
    if( !copy($source, $destination) ) {
      echo "File can't be copied! \n";
    }
    else {
      echo "htaccess.patch has been copied! \n";
    }

    $source = '/var/www/default.settings.php.patch';
    $destination = './patches/base/default.settings.php.patch';
    if( !copy($source, $destination) ) {
      echo "File can't be copied! \n";
    }
    else {
      echo "settings.php.patch has been copied! \n";
    }


    $io = $event->getIO();
    $patches_file = file_get_contents('patches/base.patches.json');

    if (is_string($patches_file)) {
      $fs = new Filesystem();
      $patches_list = json_decode($patches_file, TRUE);

      foreach ($patches_list as $patch) {
        $output_dry = [];
        exec('patch --dry-run -Np1 -i "' . $patch . '" 2>&1', $output_dry, $return_var);

        $patched_files = [];
        foreach ($output_dry as $line) {
          if (strpos($line, 'checking file ') === 0) {
            $patched_files[] = substr($line, 14);
          }
        }

        if (!$return_var) {
          $output = [];
          exec('patch -Np1 -i "' . $patch . '" 2>&1', $output, $return_var);

          if (!$return_var) {
            $io->write('Patch "' . $patch . '" successfully applied.');
          }
          else {
            $io->write('>>>>>>>');
            $io->write('Patch "' . $patch . '" could not be applied, even though dry run succeeded. Debug information below:' . "\n");

            foreach ($output as $line) {
              $io->write($line);
            }
            $io->write('<<<<<<<');
          }

          foreach ($patched_files as $patched_file) {
            if ($fs->exists(__DIR__ . '/' . $patched_file . '.orig')) {
              $io->write('File "' . $patched_file . '.orig" detected; patch was not applied cleanly.');
            }
          }
        }
        else {
          $output_reverse_dry = [];
          exec('patch --dry-run -Np1 -R -i "' . $patch . '" 2>&1', $output_reverse_dry, $return_var);

          if (!$return_var) {
            $io->write('Patch "' . $patch . '" has already been applied.');
          }
          else {
            $io->write('>>>>>>>');
            $io->write('Patch "' . $patch . '" could not be applied. Debug information below:' . "\n");

            foreach ($output_dry as $line) {
              $io->write($line);
            }
            $io->write('<<<<<<<');
          }
        }
      }
    }
    else {
      $io->write('Could not find base.patches.json.');
    }
  }

  /**
   * @param \Composer\Script\Event $event
   *
   * @throws \Exception
   */

  public static function createRequiredFiles(Event $event) {
    $fs = new Filesystem();
    $loader = require_once __DIR__ . '/../../vendor/autoload.php';
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();

    Settings::initialize($drupalRoot, 'sites/default', $loader);

    $dirs = [
      'modules',
      'profiles',
      'themes',
    ];

    // Prepare Scaffolds for Config Management
    if (!is_dir('./config/default/splits')) {
      mkdir('./config/default/splits', 0777, true);
    }
    if (!is_dir('./config/default/splits/dev')) {
      mkdir('./config/default/splits/dev', 0777, true);
    }
    if (!is_dir('./config/default/splits/live')) {
      mkdir('./config/default/splits/live', 0777, true);
    }

    // Prepare Scaffolds for local dumps
    if (!is_dir('./dumps')) {
      mkdir('./dumps', 0777, true);
    }

    // Required for unit testing
    foreach ($dirs as $dir) {
      if (!$fs->exists($drupalRoot . '/'. $dir)) {
        $fs->mkdir($drupalRoot . '/'. $dir);
        $fs->touch($drupalRoot . '/'. $dir . '/.gitkeep');
      }
    }

    // Prepare the settings file for installation
    if (!$fs->exists($drupalRoot . '/sites/default/settings.php') && $fs->exists($drupalRoot . '/sites/default/default.settings.php')) {
      $fs->copy($drupalRoot . '/sites/default/default.settings.php', $drupalRoot . '/sites/default/settings.php');
      require_once $drupalRoot . '/core/includes/bootstrap.inc';
      require_once $drupalRoot . '/core/includes/install.inc';
      new Settings([]);
      $settings['settings']['config_sync_directory'] = (object) [
        'value' => Path::makeRelative($drupalFinder->getComposerRoot() . '/config/sync', $drupalRoot),
        'required' => TRUE,
      ];
      drupal_rewrite_settings($settings, $drupalRoot . '/sites/default/settings.php');
      $fs->chmod($drupalRoot . '/sites/default/settings.php', 0666);
      $event->getIO()->write("Created a sites/default/settings.php file with chmod 0666");
    }

    /*
    * Prepare local settings file.
    */
    if (!$fs->exists($drupalRoot . '/sites/default/settings.local.php') && $fs->exists($drupalRoot . '/sites/example.settings.local.php')) {
      $fs->copy($drupalRoot . '/sites/example.settings.local.php', $drupalRoot . '/sites/default/settings.local.php');
      $fs->chmod($drupalRoot . '/sites/default/settings.local.php', 0644);
      $event->getIO()
        ->write('Scaffolded a sites/default/settings.local.php file with chmod 0644.');
    }

    /*
     * Prepare local services file.
     */
    if (!$fs->exists($drupalRoot . '/sites/default/services.yml') && $fs->exists($drupalRoot . '/sites/default/default.services.yml')) {
      $fs->copy($drupalRoot . '/sites/default/default.services.yml', $drupalRoot . '/sites/default/services.yml');
      $fs->chmod($drupalRoot . '/sites/default/services.yml', 0644);
      $event->getIO()
        ->write('Scaffolded a sites/default/services.yml file with chmod 0644.');
    }

    /*
     * Prepare development.services.yml.
     */

    $developmentServices = [];
    if ($fs->exists($drupalRoot . '/sites/development.services.yml')) {
      $developmentServices = Yaml::parse(file_get_contents($drupalRoot . '/sites/development.services.yml'));
    }

    $newDevelopmentServices = self::setDevelopmentServices($developmentServices);

    if ($developmentServices !== $newDevelopmentServices) {
      // Development: sites/development.services.yml.
      $yaml = Yaml::dump($newDevelopmentServices, 4, 2);
      file_put_contents($drupalRoot . '/sites/development.services.yml', $yaml);
      $event->getIO()
        ->write('Appended Twig debug configuration to development.services.yml');
    }

    // Create the files directory with chmod 0755.
    if (!$fs->exists($drupalRoot . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/sites/default/files', 0755);
      umask($oldmask);
      $event->getIO()
        ->write('Create a sites/default/files directory with chmod 0755');
    }

    // Create the files/tmp directory with chmod 0755.
    if (!$fs->exists($drupalRoot . '/sites/default/files/tmp')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/sites/default/files/tmp', 0755);
      umask($oldmask);
      $event->getIO()
        ->write('Create a sites/default/files/tmp directory with chmod 0755');
    }
  }
  /**
   * Remove .git directories from vendor packages.
   *
   * @param \Composer\Script\Event $event
   */
  protected static function removeGitDirectories(Event $event) {
    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();
    $vendor = $drupalFinder->getVendorDir();

    // .git directories in the vendor folder.
    exec('find ' . $vendor . ' -mindepth 2 -name \'.git\' | xargs rm -rf');

    // .git directories in contrib.
    $types = [
      'modules',
      'themes',
      'profiles',
    ];

    $sources = [
      'contrib',
      'arocom',
    ];

    foreach ($sources as $source) {
      foreach ($types as $type) {
        if ($fs->exists("{$drupalRoot}/{$type}/{$source}")) {
          exec("find {$drupalRoot}/{$type}/{$source} -mindepth 2 -name '.git' | xargs rm -rf");
        }
      }
    }

    // Don't forget libraries.

    if ($fs->exists("{$drupalRoot}/libraries")) {
      exec("find {$drupalRoot}/libraries -mindepth 2 -name '.git' | xargs rm -rf");
    }
  }

  /**
   * Clear the Redis cache.
   *
   * @param \Composer\Script\Event $event
   */

  protected static function clearRedisCache(Event $event) {
    $io = $event->getIO();
    exec('sudo apt-get install redis-server');

    $io->write('Clearing Redis cache.');

    exec('redis-cli flushall 2>&1', $output, $return_var);

    if (!$return_var) {
      $io->write('Redis cache successfully cleared.');
    }
    else {
      $io->write('>>>>>>>');
      $io->write('Redis cache could not be cleared. Debug information below:' . "\n");

      foreach ($output as $line) {
        $io->write($line);
      }
      $io->write('<<<<<<<');
    }
  }




  /**
   * Checks if the installed version of Composer is compatible.
   *
   * Composer 1.0.0 and higher consider a `composer install` without having a
   * lock file present as equal to `composer update`. We do not ship with a lock
   * file to avoid merge conflicts downstream, meaning that if a project is
   * installed with an older version of Composer the scaffolding of Drupal will
   * not be triggered. We check this here instead of in drupal-scaffold to be
   * able to give immediate feedback to the end user, rather than failing the
   * installation after going through the lengthy process of compiling and
   * downloading the Composer dependencies.
   *
   * @see https://github.com/composer/composer/pull/5035
   */
  public static function checkComposerVersion(Event $event) {
    $composer = $event->getComposer();
    $io = $event->getIO();

    $version = $composer::VERSION;

    // The dev-channel of composer uses the git revision as version number,
    // try to the branch alias instead.
    if (preg_match('/^[0-9a-f]{40}$/i', $version)) {
      $version = $composer::BRANCH_ALIAS_VERSION;
    }

    // If Composer is installed through git we have no easy way to determine if
    // it is new enough, just display a warning.
    if ($version === '@package_version@' || $version === '@package_branch_alias_version@') {
      $io->writeError('<warning>You are running a development version of Composer. If you experience problems, please update Composer to the latest stable version.</warning>');
    }
    elseif (Comparator::lessThan($version, '1.0.0')) {
      $io->writeError('<error>Drupal-project requires Composer version 1.0.0 or higher. Please update your Composer before continuing</error>.');
      exit(1);
    }
  }
  /**
   * @param array $developmentServices
   *
   * @return array
   */
  private static function setDevelopmentServices(array $developmentServices) {

    if (!array_key_exists('services', $developmentServices)) {
      $developmentServices = array_merge($developmentServices,
        ['services' => []]);
    }
    if (!array_key_exists('cache.backend.null',
      $developmentServices['services'])) {
      $developmentServices['services'] = array_merge($developmentServices['services'],
        ['cache.backend.null' => []]);
    }

    if (!array_key_exists('parameters', $developmentServices)) {
      $developmentServices = array_merge($developmentServices,
        ['parameters' => []]);
    }
    if (!array_key_exists('session.storage.options',
      $developmentServices['parameters'])) {
      $developmentServices['parameters'] = array_merge($developmentServices['parameters'],
        ['session.storage.options' => []]);
    }
    if (!array_key_exists('twig.config',
      $developmentServices['parameters'])) {
      $developmentServices['parameters'] = array_merge($developmentServices['parameters'],
        ['twig.config' => []]);
    }
    if (!array_key_exists('renderer.config',
      $developmentServices['parameters'])) {
      $developmentServices['parameters'] = array_merge($developmentServices['parameters'],
        ['renderer.config' => []]);
    }
    if (!array_key_exists('http.response.debug_cacheability_headers',
      $developmentServices['parameters'])) {
      $developmentServices['parameters'] = array_merge($developmentServices['parameters'],
        ['http.response.debug_cacheability_headers' => FALSE]);
    }
    if (!array_key_exists('factory.keyvalue',
      $developmentServices['parameters'])) {
      $developmentServices['parameters'] = array_merge($developmentServices['parameters'],
        ['factory.keyvalue' => []]);
    }
    if (!array_key_exists('factory.keyvalue.expirable',
      $developmentServices['parameters'])) {
      $developmentServices['parameters'] = array_merge($developmentServices['parameters'],
        ['factory.keyvalue.expirable' => []]);
    }
    if (!array_key_exists('filter_protocols',
      $developmentServices['parameters'])) {
      $developmentServices['parameters'] = array_merge($developmentServices['parameters'],
        ['filter_protocols' => []]);
    }
    if (!array_key_exists('cors.config',
      $developmentServices['parameters'])) {
      $developmentServices['parameters'] = array_merge($developmentServices['parameters'],
        ['cors.config' => []]);
    }

    $developmentServices['services']['cache.backend.null'] = array_merge($developmentServices['services']['cache.backend.null'],
      [
        'class' => 'Drupal\Core\Cache\NullBackendFactory',
      ]);

    $developmentServices['parameters']['session.storage.options'] = array_merge($developmentServices['parameters']['session.storage.options'],
      [
        'gc_probability' => 1,
        'gc_divisor' => 100,
        'gc_maxlifetime' => 604800,
        'cookie_lifetime' => 604800,
      ]);
    $developmentServices['parameters']['twig.config'] = array_merge($developmentServices['parameters']['twig.config'],
      [
        'debug' => TRUE,
        'auto_reload' => TRUE,
        'cache' => TRUE,
      ]);
    $developmentServices['parameters']['renderer.config'] = array_merge($developmentServices['parameters']['renderer.config'],
      [
        'required_cache_contexts' => [
          'languages:language_interface',
          'theme',
          'user.permissions',
        ],
        'auto_placeholder_conditions' => [
          'max-age' => 0,
          'contexts' => ['session', 'user'],
          'tags' => [],
        ],
      ]);
    $developmentServices['parameters']['http.response.debug_cacheability_headers'] = FALSE;

    // YAML: {}.
    $developmentServices['parameters']['factory.keyvalue'] = [];

    // YAML: {}.
    $developmentServices['parameters']['factory.keyvalue.expirable'] = [];

    $developmentServices['parameters']['filter_protocols'] = array_unique(array_merge($developmentServices['parameters']['filter_protocols'],
      [
        'http',
        'https',
        'ftp',
        'news',
        'nntp',
        'tel',
        'telnet',
        'mailto',
        'irc',
        'ssh',
        'sftp',
        'webcal',
        'rstp',
      ]), SORT_REGULAR);

    $developmentServices['parameters']['cors.config'] = array_merge($developmentServices['parameters']['cors.config'],
      [
        'enabled' => TRUE,
        'allowedHeaders' => [
          'x-csrf-token',
          'authorization',
          'content-type',
          'accept',
          'origin',
          'x-requested-with',
        ],
        'allowedMethods' => ['*'],
        'allowedOrigins' => ['https://maps.googleapis.com'],
        'exposedHeaders' => FALSE,
        'maxAge' => FALSE,
        'supportsCredentials' => FALSE,
      ]);
    return $developmentServices;
  }
}
