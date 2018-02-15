<!--
    * This Envoy file was created from the below tutorials:
    *  https://laravel.com/docs/5.0/envoy
    *  https://serversforhackers.com/c/deploying-with-envoy-cast
    *  https://serversforhackers.com/c/enhancing-envoy-deployment
    *
    * Need to install Laravel Envoy with Composer in order to run this file:
    *   composer global require "laravel/envoy=~1.0"
    *
    * To run this Envoy file, execute:
    *   envoy run deploy
-->
@servers(['prod' => 'deploy-fdm-server'])

@setup
    $composer_home = '/var/www/html';
    $repo = 'https://github.com/travisdesell/ngafid.git';
    //$repo = 'git@github.com:travisdesell/ngafid.git';
    $release_dir = '/var/www/html/NGAFID_releases';
    $app_dir = '/var/www/html/NGAFID';
    $release = 'release_' . date('YmdHis');
@endsetup

@macro('deploy', ['on' => 'prod'])
    fetch_repo
    update_env_symlink
    run_composer
    update_logs_symlink
    update_permissions
    update_app_symlink
@endmacro

@task('fetch_repo')
    [ -d {{ $release_dir }} ] || mkdir {{ $release_dir }};
    cd {{ $release_dir }};

    # Always clone from master branch
    git clone -b master {{ $repo }} {{ $release }};
@endtask

@task('run_composer')
    cd {{ $release_dir }}/{{ $release }};
    {{ $composer_home }}/composer.phar install --prefer-dist --no-scripts;
    php artisan clear-compiled --env=production;
    php artisan optimize --env=production;
@endtask

@task('update_permissions')
    cd {{ $release_dir }};
    chown -R fdm:webprogs {{ $release }};
    find {{ $release }} -type f -exec chmod 664 {} \;
    find {{ $release }} -type d -exec chmod 775 {} \;

    cd {{ $release }};
    [ -d bootstrap/cache ] || mkdir -p bootstrap/cache
    chgrp -R webprogs storage bootstrap/cache;
    chmod -R ug+rwx storage bootstrap/cache;
@endtask

@task('update_env_symlink')
    # Symlink the global .env file to this release
    cd {{ $release_dir }}/{{ $release }};
    ln -nfs ../../NGAFID.env .env;
    chgrp -h webprogs .env;
@endtask

@task('update_logs_symlink')
    # Persist log files across releases
    rm -r {{ $release_dir }}/{{ $release }}/storage/logs;
    cd {{ $release_dir }}/{{ $release }}/storage;
    ln -nfs ../../../logs logs;
    chgrp -h webprogs logs;
@endtask

@task('update_app_symlink')
    # Symlink new release dir to live NGAFID web folder
    ln -nfs {{ $release_dir }}/{{ $release }} {{ $app_dir }};
    chgrp -h webprogs {{ $app_dir }};
@endtask
