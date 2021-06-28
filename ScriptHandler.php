{
        $fs = new Filesystem();
        $loader = require_once __DIR__ . '/../../vendor/autoload.php';
        $drupalFinder = new DrupalFinder();
        $drupalFinder->locateRoot(getcwd());
        $drupalRoot = $drupalFinder->getDrupalRoot();
        $composerRoot = $drupalFinder->getComposerRoot();

        Settings::initialize($drupalRoot, 'sites/default', $loader);

        // Prepare the settings file for installation.
        if (!$fs->exists($drupalRoot . '/sites/default/settings.php') && $fs->exists($drupalRoot . '/sites/default/default.settings.php')) {
            $fs->copy($drupalRoot . '/sites/default/default.settings.php', $drupalRoot . '/sites/default/settings.php');
            $fs->chmod($drupalRoot . '/sites/default/settings.php', 0644);
            $event->getIO()
                ->write('Scaffolded a sites/default/settings.php file with chmod 0644.');
        }

        // Alter settings.php
        if ($fs->exists($composerRoot . '/settings/default/settings.php') && $fs->exists($drupalRoot . '/sites/default/default.settings.php')) {
            new Settings([]);
            $settings['settings']['config_sync_directory'] = (object)[
                'value' => Path::makeRelative($drupalFinder->getComposerRoot() . '/config/default/sync', $drupalRoot),
                'required' => TRUE,
            ];
            drupal_rewrite_settings($settings, $composerRoot . '/settings/default/settings.php');
            $settings['settings']['file_temp_path'] = (object)[
                'value' => Path::makeRelative($drupalFinder->getComposerRoot() . '/files/default/files/tmp', $drupalRoot),
                'required' => TRUE,
            ];
            drupal_rewrite_settings($settings, $composerRoot . '/settings/default/settings.php');
             $settings['settings']['redis.connection']['host'] = (object)[
                 'value' => 'redis',
                 'required' => TRUE,
             ];
             drupal_rewrite_settings($settings, $composerRoot . '/settings/default/settings.php');
             $settings['settings']['redis.connection']['port'] = (object)[
                 'value' => '6379',
                 'required' => TRUE,
             ];
             drupal_rewrite_settings($settings, $composerRoot . '/settings/default/settings.php');
             $settings['settings']['cache']['bins']['bootstrap'] = (object)[
                 'value' => 'cache.backend.chainedfast',
                 'required' => TRUE,
             ];
             drupal_rewrite_settings($settings, $composerRoot . '/settings/default/settings.php');
             $settings['settings']['cache']['bins']['discovery'] = (object)[
                 'value' => 'cache.backend.chainedfast',
                 'required' => TRUE,
             ];
             drupal_rewrite_settings($settings, $composerRoot . '/settings/default/settings.php');
             $settings['settings']['cache']['bins']['config'] = (object)[
                 'value' => 'cache.backend.chainedfast',
                 'required' => TRUE,
             ];
            drupal_rewrite_settings($settings, $composerRoot . '/settings/default/settings.php');
            $settings['settings']['update_free_access'] = (object)[
                'value' => 'FALSE',
                'required' => TRUE,
            ];
            drupal_rewrite_settings($settings, $composerRoot . '/settings/default/settings.php');
            $settings['settings']['entity_update_batch_size'] = (object)[
                'value' => '50',
                'required' => TRUE,
            ];
            drupal_rewrite_settings($settings, $composerRoot . '/settings/default/settings.php');
            $settings['settings']['entity_update_backup'] = (object)[
                'value' => 'TRUE',
                'required' => TRUE,
            ];
            drupal_rewrite_settings($settings, $composerRoot . '/settings/default/settings.php');
            $settings['settings']['migrate_node_migrate_type_classic'] = (object)[
                'value' => 'FALSE',
                'required' => TRUE,
            ];
            //$settings['databases'] = (object) array('value' => array('default' => array('default' => array('prefix' => '', 'host' => 'mariadb', 'driver' => 'mysql', 'database' => 'drupal', 'username' => 'drupal', 'password' => 'drupal', 'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql'))), 'required' => TRUE);
            drupal_rewrite_settings($settings, $composerRoot . '/settings/default/settings.php');
            $fs->chmod($composerRoot . '/settings/default/settings.php', 0666);
            $event->getIO()->write("Adjusted the settings/default/settings.php file with chmod 0666");
        }
        // Alter local.settings.php
        /* if ($fs->exists($composerRoot . '/settings/default/settings.local.php')) {
              new Settings([]);
              $settingsLocal['settings']['trusted_host_patterns'] = (object)[
                  'value' => '[".*"]',
                  'required' => TRUE,
              ];
              $settingsLocal['settings']['reverse_proxy'] = (object)[
                  'value' => TRUE,
                  'required' => TRUE,
              ];
              $settingsLocal['settings']['reverse_proxy_addresses'] = (object)[
                  'value' => array('127.0.0.1'),
                  'required' => TRUE,
              ];
              $settingsLocal['config']['system.file']['path']['temporary'] = (object)[
                  'value' => '/tmp',
                  'required' => TRUE,
              ];
              $settingsLocal['settings']['php_storage']['twig']['directory'] = (object)[
                  'value' => '/tmp/arocom-d82',
                  'required' => TRUE,

              ];*/
        $settingsLocal['settings']['hash_salt']= (object)[
            'value' => "file_get_contents(__DIR__ . '/../../salt.txt')",
            'required' => TRUE,

        ];
        /*$settingsLocal['settings']['file_scan_ignore_directories'] = (object) [
            'value' => array('node_modules', 'bower_components', 'vendor', 'styles', 'php', 'private'),
            'required' => TRUE,
        ];
        $settingsLocal['config_directories']['sync'] = (object) [
            'value' => Path::makeRelative($drupalFinder->getComposerRoot() . '/config/default/sync', $drupalRoot),
            'required' => TRUE,
        ];*/
        drupal_rewrite_settings($settingsLocal, $composerRoot . '/settings/default/settings.local.php');
        $fs->chmod($composerRoot . '/settings/default/settings.local.php', 0666);
        $event->getIO()->write("Adjusted the settings/default/local.settings.php file with chmod 0666");

        #}

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
            file_put_contents($composerRoot . '/settings/default/development.services.yml', $yaml);
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
